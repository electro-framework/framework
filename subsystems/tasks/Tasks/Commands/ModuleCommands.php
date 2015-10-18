<?php
namespace Selenia\Tasks\Commands;
use PhpKit\Flow\FilesystemFlow;
use Robo\Task\Composer\DumpAutoload;
use Robo\Task\Composer\Update;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Robo\Task\Vcs\GitStack;
use Selenia\Contracts\ApplicationServiceTrait;
use Selenia\Console\Contracts\ConsoleIOServiceTrait;
use Selenia\Console\Contracts\FileSystemStackServiceTrait;
use Selenia\Contracts\ModuleConfigServiceTrait;
use Selenia\Exceptions\HttpException;
use Selenia\Console\Lib\PackagistAPI;
use Selenia\ModulesApi;
use Selenia\Tasks\InstallPackageTask;
use Selenia\Tasks\UninstallPackageTask;

/**
 * Implements the Selenia Task Runner's pre-set build commands.
 */
trait ModuleCommands
{
  use ConsoleIOServiceTrait;
  use ApplicationServiceTrait;
  use ModuleConfigServiceTrait;
  use FileSystemStackServiceTrait;

  /**
   * Scaffolds a new project module
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be created
   */
  function moduleCreate ($moduleName = null)
  {
    $io  = $this->io ();
    $api = ModulesApi::get ();

    $moduleName = $moduleName ?: $this->io ()->askDefault ("Module name", "vendor-name/product-name");

    if (!$api->validateModuleName ($moduleName))
      $io->error ("Invalid module name $moduleName. Correct syntax: vendor-name/product-name");
    if ($api->isInstalled ($moduleName))
      $io->error ("You can't use that name because a module named $moduleName already exists");

    $___MODULE___    = $moduleName;
    $___NAMESPACE___ = ModulesApi::get ()->moduleNameToNamespace ($___MODULE___);
    $___CLASS___     = explode ('\\', $___NAMESPACE___)[1] . 'Module';
    if (!$moduleName) {
      $___NAMESPACE___ = $io->askDefault ("PHP namespace for the module's classes", $___NAMESPACE___);
      $___CLASS___     = $io->askDefault ("Name of the class that represents the module:", $___CLASS___);
    }
    $___PSR4_NAMESPACE___ = str_replace ('\\', '\\\\', "$___NAMESPACE___\\");

    $path = "{$this->app()->modulesPath}/$___MODULE___";
    (new CopyDir (["{$this->moduleConfig('scaffoldsPath')}/module" => $path]))->run ();
    $this->fs ()->rename ("$path/src/___CLASS___.php", "$path/src/$___CLASS___.php")->run ();

    foreach
    ([
       "$path/src/$___CLASS___.php",
       "$path/bootstrap.php",
       "$path/composer.json",
     ]
     as $file) (new Replace ($file))
      ->from ([
        '___MODULE___',
        '___CLASS___',
        '___NAMESPACE___',
        '___PSR4_NAMESPACE___',
        '___MODULE_PATH___',
      ])
      ->to ([
        $___MODULE___,
        $___CLASS___,
        $___NAMESPACE___,
        $___PSR4_NAMESPACE___,
        $path,
      ])
      ->run ();

    // Register the module's namespace

    $this->composerUpdate ();

    // Register the module

    $this->moduleUpdateRegistry ();

    $io->done ("Module <info>$___MODULE___</info> was created");
  }

  /**
   * Installs a plugin module
   * @param string $moduleName If not specified, a list of installable plugins will be displayed for the user
   *                           to pick one
   * @param array  $opts
   * @option $search|s Search for plugins having the specified text word or prefix somewhere on the name or description
   * @option $stars Sort the list by stars, instead of downloads
   */
  function moduleInstallPlugin ($moduleName = null, $opts = ['search|s' => '', 'stars' => false])
  {
    $io = $this->io ();
    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('selenia-plugin')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching plugins were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $sel        = $io->menu ('Select a plugin module to install:',
        array_getColumn ($modules, 'fname'), -1,
        array_getColumn ($modules, 'description'),
        function ($i) use ($modules) {
          return !ModulesApi::get ()->isInstalled ($modules[$i]['name']) ?: "That module is already installed";
        }
      );
      $moduleName = $modules[$sel]['name'];
    }

    // Install module via Composer

    (new InstallPackageTask($moduleName))->printed (false)->run ();

    // Register the module

    $this->moduleUpdateRegistry ();

    $io->done ("Plugin <info>$moduleName</info> is now installed");
  }

  /**
   * Installs a template module
   *
   * A template, when installed, becames a project-module.
   *
   * @param string $moduleName If not specified, a list of installable templates will be displayed for the user
   *                           to pick one
   * @param array  $opts
   * @option $keep-repo|k When set, the hidden .git directory is not removed from the module's directory
   * @option $search|s Search for templates having the specified text word or prefix somewhere on the name or
   *                           description
   * @option $stars Sort the list by stars, instead of downloads
   */
  function moduleInstallTemplate ($moduleName = null,
                                  $opts = ['keep-repo|k' => false, 'search|s' => '', 'stars' => false])
  {
    $io = $this->io ();
    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('selenia-template')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching templates were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $sel        = $io->menu ('Select a template module to install:',
        array_getColumn ($modules, 'fname'), -1,
        array_getColumn ($modules, 'description'),
        function ($i) use ($modules) {
          return !ModulesApi::get ()->isInstalled ($modules[$i]['name'])
            ?: "A module with that name already exists on this project";
        }
      );
      $module     = $modules[$sel];
      $moduleName = $module['name'];
      $moduleUrl  = $module['repository'];
    }
    else {

      // Extract package information from packagist.org

      try {
        $info = (new PackagistAPI)->get ($moduleName);
      } catch (HttpException $e) {
        $io->error ($e->getCode () == 404 ? "Module '$moduleName' was not found" : $e->getMessage ());
      }
      /** @noinspection PhpUndefinedVariableInspection */
      $module    = $info['package'];
      $moduleUrl = $module['repository'];
    }

    // Clone the repo.

    $path = "{$this->app()->modulesPath}/$moduleName";
    (new GitStack)->cloneRepo ($moduleUrl, $path)->printed (false)->run ();

    // Remove VCS history

    if (!$opts['keep-repo'])
      $this->fs ()->remove ("$path/.git")->run ();

    // Install the module's dependencies and register its namespaces

    $this->composerUpdate ();

    // Register the module

    $this->moduleUpdateRegistry ();

    $io->done ("Template <info>$moduleName</info> is now installed on <info>$path</info>");
  }

  /**
   * Removes a module from the application
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be uninstalled
   */
  function moduleUninstall ($moduleName = null)
  {
    ModulesApi::get ()->selectModule ($moduleName, $this->io ());

    if (ModulesApi::get ()->isPlugin ($moduleName))
      $this->uninstallPlugin ($moduleName);
    else $this->uninstallProjectModule ($moduleName);
  }

  /**
   * Syncs the manifest.json file, thereby (re)registering all currently installed modules
   */
  function moduleUpdateRegistry ()
  {
    ModulesApi::get ()->updateManifest ();
  }

  //--------------------------------------------------------------------------------------------------------------------

  protected function uninstallPlugin ($moduleName)
  {
    (new UninstallPackageTask($moduleName))->printed (false)->run ();

    $this->moduleUpdateRegistry ();

    $this->io ()->done ("Plugin module <info>$moduleName</info> was uninstalled");
  }

  protected function uninstallProjectModule ($moduleName)
  {
    $io = $this->io ();

    $path = "{$this->app()->modulesPath}/$moduleName";
    $this->removeModuleDirectory ($path);

    // Uninstall the module's dependencies and unregister its namespaces

    $this->composerUpdate ();

    // Unregister the module

    $this->moduleUpdateRegistry ();

    $io->done ("Module <info>$moduleName</info> was uninstalled");
  }

  //--------------------------------------------------------------------------------------------------------------------

  private function dumpAutoLoad ()
  {
    (new DumpAutoload)->printed (false)->run ();
  }

  private function composerUpdate ()
  {
    (new Update)->printed (false)->run ();
  }

  private function formatModules (& $modules, $stars = false)
  {
    // Sort list

    $modules = $stars
      ? array_orderBy ($modules, 'favers', SORT_DESC, 'downloads', SORT_DESC)
      : array_orderBy ($modules, 'downloads', SORT_DESC);

    // Format display

    $starsW = max (array_map ('strlen', array_column ($modules, 'favers')));
    array_walk ($modules, function (&$m) use ($starsW) {
      $i = ModulesApi::get ()->isInstalled ($m['name']);
      list ($vendor, $package) = explode ('/', $m['name']);
      $stats = "<comment>" .
               str_pad ($m['downloads'], 6, ' ', STR_PAD_LEFT) . "▾  " .
               str_pad ($m['favers'], $starsW, ' ', STR_PAD_LEFT) . "★" .
               "</comment>";

      $m['fname']       = $i
        ? "<comment>$vendor/$package</comment>"
        : "<info>$vendor/</info>$package";
      $m['description'] = "$stats  {$m['description']}" .
                          ($i ? ' <info>(installed)</info>' : '');
    });
  }

  /**
   * Check if a directory is empty.
   * @param string $path
   * @return bool
   */
  private function isDirectoryEmpty ($path)
  {
    return !count (FilesystemFlow::from ($path)->onlyDirectories ()->all ());
  }

  private function removeModuleDirectory ($path)
  {
    if (file_exists ($path)) {
      (new DeleteDir($path))->run ();
      // Remove vendor dir. when it becomes empty.
      $vendorPath = dirname ($path);
      if ($this->isDirectoryEmpty ($vendorPath))
        (new DeleteDir($vendorPath))->run ();
    }
    else $this->io ()
              ->warn ("No module files were deleted because none were found on the <info>modules</info> directory");
  }

}
