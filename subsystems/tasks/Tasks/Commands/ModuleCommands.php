<?php
namespace Selenia\Tasks\Commands;

use PhpKit\Flow\FilesystemFlow;
use Robo\Task\Composer\Update;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Robo\Task\FileSystem\FilesystemStack;
use Robo\Task\Vcs\GitStack;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Core\ConsoleApplication\Lib\ModulesUtil;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Lib\PackagistAPI;
use Selenia\Migrations\Config\MigrationsSettings;
use Selenia\Tasks\Config\TasksSettings;
use Selenia\Tasks\Shared\InstallPackageTask;
use Selenia\Tasks\Shared\UninstallPackageTask;

/**
 * Implements the Selenia Task Runner's pre-set build commands.
 *
 * @property Application        $app
 * @property TasksSettings      $settings
 * @property MigrationsSettings $migrationsSettings
 * @property ConsoleIOInterface $io
 * @property FilesystemStack    $fs
 * @property ModulesUtil        $modulesUtil
 * @property ModulesRegistry    $modulesRegistry
 */
trait ModuleCommands
{
  /**
   * @var bool Should install-plugin prefer stable versions?
   */
  static $INSTALL_STABLE = false;
  /**
   * @var bool Display the output of Composer commands?
   */
  static $SHOW_COMPOSER_OUTPUT = true;

  /**
   * Drops the database tables of the specified module
   *
   * @param string $moduleName [optional] The full name (vendor-name/module-name) of the module to be cleaned up
   * @param array  $opts
   * @option $suppress-errors|s Do not output an error if the module name doesn't mactch a valid module
   */
  function moduleCleanUp ($moduleName = '', $opts = ['suppress-errors|s' => false])
  {
    if ($this->modulesUtil->selectModule ($moduleName, false, true)) {
      $this->modulesRegistry->getInstaller ()->cleanUpModule ($moduleName);
      $this->io->done ("Cleanup completed");
    }
    else if (!$opts['suppress-errors'])
      $this->io->error ("<error-info>$moduleName</error-info> is not a module");
  }

  /**
   * Scaffolds a new project module
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be created
   */
  function makeModule ($moduleName = null)
  {
    $io = $this->io;

    $moduleName = $moduleName ?: $io->askDefault ("Module name", "company-name/project-name");

    if (!$this->modulesRegistry->validateModuleName ($moduleName))
      $io->error ("Invalid module name $moduleName. Correct syntax: company-name/project-name");
    if ($this->modulesRegistry->isInstalled ($moduleName))
      $io->error ("You can't use that name because a module named $moduleName already exists");

    $___MODULE___    = $moduleName;
    $___NAMESPACE___ = ModuleInfo::moduleNameToNamespace ($___MODULE___);
    $___CLASS___     = explode ('\\', $___NAMESPACE___)[1] . 'Module';
    if (!$moduleName) {
      $___NAMESPACE___ = $io->askDefault ("PHP namespace for the module's classes", $___NAMESPACE___);
      $___CLASS___     = $io->askDefault ("Name of the class that represents the module:", $___CLASS___);
    }
    $___PSR4_NAMESPACE___ = str_replace ('\\', '\\\\', "$___NAMESPACE___\\");

    $path = "{$this->app->modulesPath}/$___MODULE___";
    (new CopyDir (["{$this->settings->scaffoldsPath()}/module" => $path]))->run ();
    $this->fs->rename ("$path/src/Config/___CLASS___.php", "$path/src/Config/$___CLASS___.php")->run ();

    foreach
    ([
       "$path/src/Config/$___CLASS___.php",
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

    $this->composerUpdate (); // It also updates the modules registry

    $io->done ("Module <info>$___MODULE___</info> was created");
  }

  /**
   * Disables a module
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be disabled
   */
  function moduleDisable ($moduleName = null)
  {
    $this->modulesUtil->selectModule ($moduleName);

    $module          = $this->modulesRegistry->getModule ($moduleName);
    $module->enabled = false;
    $this->modulesRegistry->save ();

    $this->io->done ("Module <info>$moduleName</info> was disabled");
  }

  /**
   * Enables a module
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be enabled
   */
  function moduleEnable ($moduleName = null)
  {
    $this->modulesUtil->selectModule ($moduleName);

    $module          = $this->modulesRegistry->getModule ($moduleName);
    $module->enabled = true;
    $this->modulesRegistry->save ();

    $this->io->done ("Module <info>$moduleName</info> was enabled");
  }

  /**
   * Installs a plugin module
   *
   * @param string $moduleName If not specified, a list of installable plugins will be displayed for the user
   *                           to pick one
   * @param array  $opts
   * @option $search|s Search for plugins having the specified text word or prefix somewhere on the name or description
   * @option $stars Sort the list by stars, instead of downloads
   */
  function moduleInstallPlugin ($moduleName = null, $opts = ['search|s' => '', 'stars' => false])
  {
    $io = $this->io;
    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('selenia-plugin')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching plugins were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $sel = $io->menu ('Select a plugin module to install:',
        array_getColumn ($modules, 'fname'), -1,
        array_getColumn ($modules, 'description'),
        function ($i) use ($modules) {
          return !$this->modulesRegistry->isInstalled ($modules[$i]['name']) ?: "That module is already installed";
        }
      );
      if ($sel < 0) $this->io->cancel ();
      $moduleName = $modules[$sel]['name'];
    }

    // Install module via Composer

    $version = self::$INSTALL_STABLE ? '' : ':dev-master';
    // Note: this also updates the modules registry.
    (new InstallPackageTask("$moduleName$version"))->printed (self::$SHOW_COMPOSER_OUTPUT)->run ();

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
    $io = $this->io;

    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('selenia-template')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching templates were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $sel = $io->menu ('Select a template module to install:',
        array_getColumn ($modules, 'fname'), -1,
        array_getColumn ($modules, 'description'),
        function ($i) use ($modules) {
          return !$this->modulesRegistry->isInstalled ($modules[$i]['name'])
            ?: "A module with that name already exists on this project";
        }
      );
      if ($sel < 0) $this->io->cancel ();
      $module     = $modules[$sel];
      $moduleName = $module['name'];
      $moduleUrl  = $module['repository'];
    }
    else {
      // Extract package information from packagist.org

      try {
        $info = (new PackagistAPI)->get ($moduleName);
      }
      catch (HttpException $e) {
        $io->error ($e->getCode () == 404 ? "Module '$moduleName' was not found" : $e->getMessage ());
      }
      /** @noinspection PhpUndefinedVariableInspection */
      $module    = $info['package'];
      $moduleUrl = $module['repository'];
    }

    // Clone the repo.

    $path = "{$this->app->modulesPath}/$moduleName";
    (new GitStack)->cloneRepo ($moduleUrl, $path)->printed (false)->run ();

    // Remove VCS history

    if (!$opts['keep-repo'])
      $this->fs->remove ("$path/.git")->run ();

    // Install the module's dependencies and register its namespaces

    $this->composerUpdate (); // Note: this also updates the modules registry.

    $io->done ("Template <info>$moduleName</info> is now installed on <info>$path</info>");
  }

  /**
   * Updates the application's modules configuration to match those that are currently installed
   */
  function moduleRefresh ()
  {
    $this->modulesRegistry->refresh ();
  }

  /**
   * Displays information about the application's current modules configuration
   *
   * @param array $opts
   * @option $all|a When true, internal subsystem modules will also be displayed
   */
  function moduleStatus ($opts = ['all|a' => false])
  {
    $modules = $opts['all']
      ? $this->modulesRegistry->getAllModules ()
      : $this->modulesRegistry->getApplicationModules ();
    $o       = [];
    foreach ($modules as $module)
      $o[] = [
        $module->name,
        $module->enabled ? '<info>Yes</info>' : '<red>No</red>',
        $module->enabled && $module->bootstrapper && !$module->errorStatus ? '<info>Yes</info>' : '<red>No</red>',
        $module->errorStatus ? "<error>$module->errorStatus</error>" : '<info>OK</info>',
      ];
    $this->io->table ([
      'Module',
      'Enabled',
      'Booted',
      'Status',
    ], $o, [40, 8, 7, 0], ['L', 'C', 'C']);
  }

  /**
   * Removes a module from the application
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be uninstalled
   */
  function moduleUninstall ($moduleName = null)
  {
    $this->modulesUtil->selectModule ($moduleName);

    $this->io->writeln ("Uninstalling <info>$moduleName</info>")->nl ();

    if ($this->modulesRegistry->isPlugin ($moduleName))
      $this->uninstallPlugin ($moduleName);
    else $this->uninstallProjectModule ($moduleName);
  }

  protected function uninstallPlugin ($moduleName)
  {
    // This also updates the modules registry.
    (new UninstallPackageTask($moduleName))->printed (self::$SHOW_COMPOSER_OUTPUT)->run ();

    $this->io->done ("Plugin module <info>$moduleName</info> was uninstalled");
  }

  //--------------------------------------------------------------------------------------------------------------------

  protected function uninstallProjectModule ($moduleName)
  {
    !$this->modulesRegistry->getInstaller ()->cleanUpModule ($moduleName) or exit (1);

    $io = $this->io;
    $io->nl ();

    // Unregister the module now, otherwise a class not found error will be displayed when moduleRefresh is called.
    $this->modulesRegistry->unregisterModule ($moduleName) or exit (1);

    $path = "{$this->app->modulesPath}/$moduleName";
    $this->removeModuleDirectory ($path);

    // Uninstall the module's dependencies and unregister its namespaces.

    $this->composerUpdate (); // Note: this also updates the modules registry.

    $io->done ("Module <info>$moduleName</info> was uninstalled");
  }

  private function composerUpdate ()
  {
    (new Update)->printed (self::$SHOW_COMPOSER_OUTPUT)->run ();
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
      $i = $this->modulesRegistry->isInstalled ($m['name']);
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
   *
   * @param string $path
   * @return bool
   */
  private function isDirectoryEmpty ($path)
  {
    return !count (FilesystemFlow::from ($path)->all ());
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
    else $this->io
      ->warn ("No module files were deleted because none were found on the <info>modules</info> directory");
  }

}
