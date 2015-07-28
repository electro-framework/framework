<?php
namespace Selene\Commands;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Selene\Lib\ApplicationConfigHandler;
use Selene\Lib\ComposerConfigHandler;
use Selene\Lib\PackagistAPI;
use Selene\Tasks\InstallPackageTask;
use Selene\Tasks\UninstallPackageTask;
use Selene\Traits\CommandAPIInterface;

/**
 * Implements the Selene Task Runner's pre-set build commands.
 */
trait ModuleCommands
{
  use CommandAPIInterface;

  /**
   * Scaffolds a new module for your application
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be created
   */
  function moduleCreate ($moduleName = null)
  {
    $___MODULE___    = $moduleName ?: $this->askDefault ("Module name", "vendor-name/module-name");
    $___NAMESPACE___ = $this->moduleNameToNamespace ($___MODULE___);
    $___CLASS___     = explode ('\\', $___NAMESPACE___)[1] . 'Module';
    if (!$moduleName) {
      $___NAMESPACE___ = $this->askDefault ("PHP namespace for the module's classes", $___NAMESPACE___);
      $___CLASS___     = $this->askDefault ("Name of the class that represents the module:", $___CLASS___);
    }

    $path = "{$this->app()->modulesPath}/$___MODULE___";
    if (file_exists ($path) || file_exists ("{$this->app()->defaultModulesPath}/$___MODULE___"))
      $this->error ("Module '$___MODULE___' already exists");

    (new CopyDir (["{$this->app()->scaffoldsPath}/module" => $path]))->run ();
    $this->fs ()->rename ("$path/src/Config/___CLASS___.php", "$path/src/Config/$___CLASS___.php")->run ();

    $from = [
      '___MODULE___',
      '___CLASS___',
      '___NAMESPACE___',
    ];
    $to   = [
      $___MODULE___,
      $___CLASS___,
      $___NAMESPACE___,
    ];

    (new Replace ("$path/src/$___CLASS___.php"))->from ($from)->to ($to)->run ();
    (new Replace ("$path/bootstrap.php"))->from ($from)->to ($to)->run ();

    $composerConfig                            = new ComposerConfigHandler;
    $composerConfig->psr4 ()->$___NAMESPACE___ = 'src';
    $composerConfig->save ();

    $this->done ("Module <info>$___MODULE___</info> was created");

    $this->moduleRegister ($___MODULE___);
  }

  /**
   * Installs a plugin module
   * @param string $moduleName If not specified, a list of installable plugins will be displayed for the user
   *                           to pick one
   * @param array  $opts
   * @option $stars Sort the list by stars, instead of downloads
   */
  function moduleInstall ($moduleName = null, $opts = ['stars' => false])
  {
    if (!$moduleName) {
//      $modules = (new PackagistAPI)->query ('knplabs')->search (true);
//      $modules = (new PackagistAPI)->vendor ('selene-framework')->getAll ();
      $modules = (new PackagistAPI)->tags ('selene-framework', 'module')->search (true);
      $modules = $opts['stars']
        ? array_orderBy ($modules, 'favers', SORT_DESC, 'downloads', SORT_DESC)
        : array_orderBy ($modules, 'downloads', SORT_DESC);
      $starsW  = max (array_map ('strlen', array_column ($modules, 'favers')));
      array_walk ($modules, function (&$m) use ($starsW) {
        list ($vendor, $package) = explode ('/', $m['name']);
        $stats = "<comment>" .
                 str_pad ($m['downloads'], 6, ' ', STR_PAD_LEFT) . "▾  " .
                 str_pad ($m['favers'], $starsW, ' ', STR_PAD_LEFT) . "★" .
                 "</comment>";

        $m['name']        = "<question>$vendor/</question>$package";
        $m['description'] = "$stats  {$m['description']}";
      });
      $sel        = $this->menu ('Select a plugin module to install',
        array_getColumn ($modules, 'name'), -1,
        array_getColumn ($modules, 'description'));
      $moduleName = $modules[$sel]['name'];

      (new InstallPackageTask($moduleName))->run ();

      $this->done ("Plugin <info>$moduleName</info> is now installed");
    }
  }

  /**
   * Registers a module on the application's configuration, therefore enabling it for use
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be registered
   */
  function moduleRegister ($moduleName = '')
  {
    if (!$moduleName)
      $moduleName = $this->askDefault ("Module name", "vendor-name/module-name");
    if (!$moduleName || !strpos ($moduleName, '/'))
      $this->error ("Invalid module name");

    (new ApplicationConfigHandler)
      ->changeRegisteredModules (function (array $modules) use ($moduleName) {
        $modules[] = $moduleName;
        return $modules;
      })
      ->save ();

    $this->done ("Module <info>$moduleName</info> was registered");
  }

  /**
   * Removes a module from the application
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be uninstalled
   */
  function moduleUninstall ($moduleName = null)
  {
    $this->changeRegisteredModules (
      function (array $modules) use (&$moduleName) {
        if ($moduleName) {
          $i = array_search ($moduleName, $modules);
          if ($i === false)
            $this->error ("Module $moduleName is not registered");
        }
        else {
          $i          = $this->menu ("Select a module to unregister:", $modules);
          $moduleName = $modules[$i];
        }
        array_splice ($modules, $i, 1);

        return $modules;
      }
    );
    $config = new ComposerConfigHandler;
    if (isset($config->data->require->$moduleName))
      $this->uninstallPlugin ($moduleName);
    else $this->uninstallLocalModule ($moduleName);

    $this->done ("Module <info>$moduleName</info> was uninstalled");
  }

  /**
   * Removes a module from the application's configuration, therefore disabling it
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be unregistered
   */
  function moduleUnregister ($moduleName = null)
  {
    if ($moduleName && !strpos ($moduleName, '/'))
      $this->error ("Invalid module name");

    (new ApplicationConfigHandler)
      ->changeRegisteredModules (
        function (array $modules) use (&$moduleName) {
          if ($moduleName) {
            $i = array_search ($moduleName, $modules);
            if ($i === false)
              $this->error ("Module $moduleName is not registered");
          }
          else {
            $i          = $this->menu ("Select a module to unregister:", $modules);
            $moduleName = $modules[$i];
          }
          array_splice ($modules, $i, 1);

          return $modules;
        }
      )
      ->save ();

    $this->done ("Module <info>$moduleName</info> was unregistered");
  }

  //--------------------------------------------------------------------------------------------------------------------

  protected function uninstallLocalModule ($moduleName)
  {
    $path = "{$this->app()->modulesPath}/$moduleName";
    if (file_exists ($path))
      (new DeleteDir($path))->run ();
    else $this->warn ("No module files were deleted because none were found on the <info>modules</info> directory");

    $namespace = $this->moduleNameToNamespace ($moduleName);

    $composerConfig = new ComposerConfigHandler;
    unset ($composerConfig->psr4 ()->$namespace);
    $composerConfig->save ();
  }

  protected function uninstallPlugin ($pluginName)
  {
    $path = "{$this->app()->defaultModulesPath}/$pluginName";
    if (!file_exists ($path))
      $this->warn ("No module files were deleted because none were found on the <info>plugins</info> directory");

    (new UninstallPackageTask($pluginName))->run ();
  }

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * @param string $moduleName
   * @return string
   */
  private function moduleNameToNamespace ($moduleName)
  {
    $o = explode ('/', $moduleName);
    if (count ($o) != 2)
      $this->error ("Invalid module name");
    list ($vendor, $module) = $o;
    $namespace1 = ucfirst (dehyphenate ($vendor));
    $namespace2 = ucfirst (dehyphenate ($module));

    return "$namespace1\\$namespace2";
  }

}
