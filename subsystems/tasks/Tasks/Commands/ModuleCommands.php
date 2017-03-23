<?php

namespace Electro\Tasks\Commands;

use Electro\ConsoleApplication\Lib\ModulesUtil;
use Electro\Exceptions\HttpException;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\ModulesInstaller;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Lib\PackagistAPI;
use Electro\Plugins\IlluminateDatabase\Config\MigrationsSettings;
use Electro\Tasks\Config\TasksSettings;
use Electro\Tasks\Shared\InstallPackageTask;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Robo\Task\FileSystem\FilesystemStack;
use Robo\Task\Vcs\GitStack;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Implements the Electro Task Runner's pre-set build commands.
 *
 * @property KernelSettings     $kernelSettings
 * @property TasksSettings      $settings
 * @property MigrationsSettings $migrationsSettings
 * @property ConsoleIOInterface $io
 * @property FilesystemStack    $fs
 * @property ModulesUtil        $modulesUtil
 * @property ModulesRegistry    $modulesRegistry
 * @property ModulesInstaller   $modulesInstaller
 */
trait ModuleCommands
{

  /**
   * Installs a plugin or a template
   *
   * @param string $moduleType Either <info>plugin</info>|<info>template</info>|<info>package</info>.
   *                           If not specified, it will be asked for
   * @param string $moduleName A full module name (in <comment>vendor/package</comment> format), If not specified, a
   *                           list of installable modules will be displayed for the user to pick one
   * @param array  $opts
   * @option $search|s Search for modules having the specified text word or prefix somewhere on the name or description
   * @option $stars Sort the list by stars, instead of downloads
   * @option $unstable|u When set, the latest development version is installed instead of the latest stable version
   *                           (for plugins only)
   * @option $keep-repo|k When set, the hidden .git directory is not removed from the template's directory
   *                           (for templates only)
   */
  function install ($moduleType = null, $moduleName = null,
                    $opts = ['search|s' => '', 'stars' => false, 'unstable|u' => false, 'keep-repo|k' => false])
  {
    $io = $this->io;
    if (!$moduleType)
      $moduleType = ['plugin', 'template', 'package']
      [$io->menu ('What type of module do you want to install?', [
        'Plugin',
        'Template',
        'Standalone package',
      ], 0)];
    else $io->nl ();
    switch ($moduleType) {
      case 'plugin':
        $this->installPlugin ($moduleName, $opts);
        return;
      case 'template':
        $this->installTemplate ($moduleName, $opts);
        return;
      case 'package':
        if (!$moduleName) {
          $moduleName = $io->ask ("Type in the full package name (vendor/package format):");
          if (!$moduleName)
            $io->cancel ();
        }
        $this->installPlugin ($moduleName, $opts);
        return;
      default:
        $io->error ("Invalid module type: <error-info>$moduleType</error-info>");
    }
  }

  /**
   * Scaffolds a private module that uses the Matisse templating engine
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be created
   */
  function makeMatisseModule ($moduleName = null)
  {
    $this->makeModuleFromScaffold ($moduleName, 'matisse-module');
  }

  /**
   * Scaffolds a basic private module with routing, navigation and PHP templating
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be created
   */
  function makeModule ($moduleName = null)
  {
    $this->makeModuleFromScaffold ($moduleName, 'module');
  }

  /**
   * Runs a module's post-uninstallation tasks
   *
   * <p>This drops the database tables of the specified module (if any).
   *
   * @param string $moduleName [optional] The full name (vendor-name/module-name) of the module to be cleaned up
   * @param array  $opts
   * @option $suppress-errors|s Do not output an error if the module name doesn't mactch a valid module
   */
  function moduleCleanup ($moduleName = '', $opts = ['suppress-errors|s' => false])
  {
    if ($this->modulesUtil->selectInstalledModule ($moduleName, null, true)) {
      $this->modulesInstaller->cleanUpModule ($moduleName);
      $this->io->done ("Cleanup complete");
    }
    else if (!$opts['suppress-errors'])
      $this->io->error ("<error-info>$moduleName</error-info> is not a module");
  }

  /**
   * Disables a module
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be disabled
   */
  function moduleDisable ($moduleName = null)
  {
    $this->modulesUtil->selectInstalledModule ($moduleName);

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
    $this->modulesUtil->selectInstalledModule ($moduleName);

    $module          = $this->modulesRegistry->getModule ($moduleName);
    $module->enabled = true;
    $this->modulesRegistry->save ();

    $this->io->done ("Module <info>$moduleName</info> was enabled");
  }

  /**
   * (Re)publishes all module's public folders
   */
  function modulePublish ()
  {
    $links = $this->modulesInstaller->publishModules ();

    if ($this->io->getOutput ()->getVerbosity () >= OutputInterface::VERBOSITY_VERBOSE)
      $this->io->table (['Source', 'Target'], $links, [0, 0]);
    $this->io->done ("Published");
  }

  /**
   * Forces an update of the module registry
   *
   * <p>It updates the configuration to register those modules that are currently installed and unregister those that
   * are no longer installed.
   */
  function moduleRefresh ()
  {
    $this->modulesInstaller->rebuildRegistry ();
  }

  /**
   * (Re)runs a module's post-installation tasks
   *
   * If an argument is not given, a module can be selected interactively from a list of available modules.
   *
   * > Tip: you may also use `workman init` to reinitialize all modules.
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be reinitialized
   */
  function moduleReinit ($moduleName = null)
  {
    if ($this->modulesUtil->selectInstalledModule ($moduleName)) {
      $this->modulesInstaller->setupModule ($moduleName, true);
      $this->io->done ("Reinitialization complete");
    }
  }

  /**
   * Renames a private module
   *
   * @param string $oldModuleName The full name (vendor-name/module-name) of the module to be renamed
   * @param string $newModuleName The new name (vendor-name/module-name)
   */
  function moduleRename ($oldModuleName = null, $newModuleName = null)
  {
    $this->modulesUtil->selectInstalledModule ($oldModuleName,
      function (ModuleInfo $module) { return $module->type == ModuleInfo::TYPE_PRIVATE; });

    $this->io->writeln ("Uninstalling <info>$oldModuleName</info>")->nl ();

    if ($this->modulesRegistry->isPlugin ($oldModuleName))
      $this->uninstallPlugin ($oldModuleName);
    else $this->uninstallProjectModule ($oldModuleName);
  }

  /**
   * Displays information about the currently registered modules
   *
   * @param array $opts
   * @option $all|a When true, internal subsystem modules will also be displayed
   */
  function moduleStatus ($opts = ['all|a' => false])
  {
    $reg = $this->modulesRegistry;
    if (!$opts['all'])
      $reg->onlyPrivateOrPlugins ();
    $modules = $reg->getModules ();
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
      'Bootable',
      'Status',
    ], $o, [40, 8, 9, 0], ['L', 'C', 'C']);
  }

  /**
   * Uninstalls a plugin or a private module (alias of <info>uninstall</info>)
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be uninstalled
   */
  function remove ($moduleName = null)
  {
    $this->uninstall ($moduleName);
  }

  /**
   * Uninstalls a plugin or a private module
   *
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be uninstalled
   */
  function uninstall ($moduleName = null)
  {
    if (!$moduleName) {
      $privateModules = $this->modulesRegistry->onlyPrivate ()->getModules ();
      $plugins        = $this->modulesRegistry->onlyPluginsRequiredByModules ()->getModules ();

      $this->modulesUtil->selectModule ($moduleName, array_merge ($privateModules, $plugins));
    }
    $this->io->writeln ("Uninstalling <info>$moduleName</info>")->nl ();

    if ($this->modulesRegistry->isPlugin ($moduleName))
      $this->uninstallPlugin ($moduleName);
    else $this->uninstallProjectModule ($moduleName);
  }

  /**
   * Installs a plugin module
   *
   * @param string $moduleName If not specified, a list of installable plugins will be displayed for the user
   *                           to pick one
   * @param array  $opts
   * @option $search|s Search for plugins having the specified text word or prefix somewhere on the name or description
   * @option $stars Sort the list by stars, instead of downloads
   * @option $unstable|u When set, the latest development version is installed instead of the latest stable version
   */
  protected function installPlugin ($moduleName = null,
                                    $opts = ['search|s' => '', 'stars' => false, 'unstable|u' => false])
  {
    $io      = $this->io;
    $modules = $this->modulesRegistry->onlyPrivate ()->getModules ();

    if (!$modules)
      $io->error ("You can't install plugins until you have created one or more project modules");

    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('electro-plugin')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching plugins were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $io->banner ("PLUGINS");
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

    // Select target module where the plugin will be registered

    $io->writeln ('<question>Where should the plugin be registered?</question>');
    $this->modulesUtil->selectModule ($targetModuleName, $modules);

    // Install module via Composer

    $version      = $opts['unstable'] ? ':dev-master' : '';
    $targetModule = $this->modulesRegistry->getModule ($targetModuleName);

    // Add package reference to the targat module's composer.json
    $task = new InstallPackageTask ("$moduleName$version");
    $task->dir ($targetModule->path)
         ->option ('--no-update')
         ->printed (self::$SHOW_COMPOSER_OUTPUT)
         ->run ();

    $this->regenerateComposer (); // The module and its dependencies will no longer be considered.
    $this->doComposerUpdate ();   // This also updates the modules registry.

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
  protected function installTemplate ($moduleName = null,
                                      $opts = ['keep-repo|k' => false, 'search|s' => '', 'stars' => false])
  {
    $io = $this->io;

    if (!$moduleName) {

      // Search

      $modules = (new PackagistAPI)->type ('electro-template')->query ($opts['search'])->search (true);

      if (empty($modules))
        $io->error ("No matching templates were found");

      $this->formatModules ($modules, $opts['stars']);

      // Show menu

      $io->banner ("TEMPLATES");
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

    $path = "{$this->kernelSettings->modulesPath}/$moduleName";
    (new GitStack)->cloneRepo ($moduleUrl, $path)->printed (false)->run ();

    // Remove VCS history

    if (!$opts['keep-repo']) {
      $io->mute ();
      $this->fs->remove ("$path/.git")->run ();
      $io->unmute ();
    }

    $this->regenerateComposer (); // Install the module's dependencies and register its namespaces.
    $this->doComposerUpdate ();   // This also updates the modules registry.

    $io->done ("Template <info>$moduleName</info> is now installed on <info>$path</info>");
  }

  protected function uninstallPlugin ($moduleName)
  {
    $privateModules = $this->modulesRegistry->onlyPrivate ()->getModules ();
    foreach ($privateModules as $priv) {
      $cfg = $priv->getComposerConfig ();
      if (isset($cfg->get ('require')[$moduleName])) {
        $cfg->unrequire ($moduleName)->save ();
        $this->regenerateComposer (); // The plugin and its dependencies will no longer be considered.
        $this->doComposerUpdate ();   // This also updates the modules registry.
        $this->io->done ("Plugin <info>$moduleName</info> was uninstalled");
        return;
      };
    }
    $this->io->error ("Plugin <info>$moduleName</info> was not found on any project module");
  }

  protected function uninstallProjectModule ($moduleName)
  {
    $this->modulesInstaller->cleanUpModule ($moduleName);

    $io = $this->io;
    $io->nl ();

    // Physically remove the module.
    $path = "{$this->kernelSettings->modulesPath}/$moduleName";
    $this->removeModuleDirectory ($path);

    $this->regenerateComposer (); // The module and its dependencies will no longer be considered.
    $this->doComposerUpdate ();   // This also updates the modules registry.

    $io->done ("Module <info>$moduleName</info> was uninstalled");
  }

  //--------------------------------------------------------------------------------------------------------------------

  private function formatModules (& $modules, $stars = false)
  {
    $modules = filter ($modules, function ($mod) {
      $info = (new PackagistAPI)->get ($mod['name'])['package'];
      return !isset($info['abandoned']);
    });

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
   * Scaffolds a new project module
   *
   * @param string|null $moduleName The full name (vendor-name/module-name) of the module to be created
   * @param string      $scaffold   The scaffold's folder name.
   */
  private function makeModuleFromScaffold ($moduleName, $scaffold)
  {
    $io = $this->io;

    $moduleName = $moduleName ?: $io->askDefault ("Module name", "company-name/project-name");

    if (!ModulesRegistry::validateModuleName ($moduleName))
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

    $path = "{$this->kernelSettings->modulesPath}/$___MODULE___";
    if (is_dir ($path)) {
      $io->say ("A directory for that module already exists, but the module is not registered.
If you proceed, the directory contents will be discarded.");
      if (!$io->confirm ("Proceed"))
        $io->cancel ();
      (new DeleteDir($path))->run ();
    }

    $io->mute ();

    (new CopyDir (["{$this->settings->scaffoldsPath()}/$scaffold" => $path]))->run ();
    $this->fs->rename ("$path/src/Config/___CLASS___.php", "$path/src/Config/$___CLASS___.php")->run ();

    foreach
    ([
       "$path/src/Config/$___CLASS___.php",
       "$path/src/Config/Navigation.php",
       "$path/src/Config/Routes.php",
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

    $io->unmute ();

    $this->regenerateComposer (); // It also updates the modules registry
    $this->doComposerUpdate ();

    $io->done ("Module <info>$___MODULE___</info> was created");
  }

  private function removeModuleDirectory ($path)
  {
    $io = $this->io;
    if (file_exists ($path)) {
      $io->mute ();
      (new DeleteDir($path))->run ();
      // Remove vendor dir. when it becomes empty.
      $vendorPath = dirname ($path);
      if ($this->isDirectoryEmpty ($vendorPath))
        (new DeleteDir($vendorPath))->run ();
      $io->unmute ();
    }
    else $io->warn ("No module files were deleted because none were found on the <info>$path</info> directory");
  }

}
