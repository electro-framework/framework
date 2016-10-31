<?php
namespace Electro\Core\Assembly\Services;

use Auryn\InjectionException;
use Electro\Application;
use Electro\Core\Assembly\Lib\DependencySorter;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\ConsoleApplication\ConsoleApplication;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\Migrations\MigrationsInterface;
use Electro\Interfaces\ProfileInterface;
use Electro\Interop\MigrationStruct;
use Electro\Lib\JsonFile;
use PhpKit\Connection;
use PhpKit\Flow\FilesystemFlow;
use SplFileInfo;

/**
 * Manages modules installation, update and removal, and it also (re)builds the registry.
 *
 * > <p>**Warning:** no validation of module names is performed on methods of this class. It is assumed this service is
 * only invoked for valid modules. Validation should be performed on the caller.
 */
class ModulesInstaller
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var ConsoleIOInterface
   */
  private $io;
  /**
   * @var MigrationsInterface Lazily loaded on demand.
   */
  private $migrationsAPI;
  /**
   * @var callable Returns MigrationsInterface
   */
  private $migrationsAPIFactory;
  /**
   * @var ProfileInterface
   */
  private $profile;
  /**
   * @var ModulesRegistry
   */
  private $registry;

  function __construct (Application $app, ConsoleApplication $consoleApp, ModulesRegistry $modulesRegistry,
                        callable $migrationsAPIFactory, ProfileInterface $profile)
  {
    $this->app                  = $app;
    $this->consoleApp           = $consoleApp;
    $this->io                   = $consoleApp->getIO ();
    $this->registry             = $modulesRegistry;
    $this->migrationsAPIFactory = $migrationsAPIFactory;
    $this->profile              = $profile;
  }

  /**
   * @param ModuleInfo[] $modules
   * @return string[]
   */
  static private function getNames (array $modules)
  {
    return map ($modules, function (ModuleInfo $module) { return $module->name; });
  }

  /**
   * @param string[]     $names
   * @param ModuleInfo[] $modules
   * @return ModuleInfo[]
   */
  static private function getOnly (array $names, array $modules)
  {
    return map ($names, function ($name) use ($modules) {
      list ($module, $i) = array_find ($modules, 'name', $name);
      if (!$module) throw new \RuntimeException ("Module not found: $name");
      return $module;
    });
  }

  /**
   * Performs uninstallation clean up tasks before the module is actually uninstalled.
   *
   * @param string $moduleName
   */
  function cleanUpModule ($moduleName)
  {
    $io = $this->io;
    $io->writeln ("Cleaning up <info>$moduleName</info>");
    $migrationsAPI = $this->getMigrationsAPI ();
    $migrations    = $migrationsAPI->module ($moduleName)->status ();
    if ($migrations) {
      $io->nl ()->comment ("    The module has migrations.");
      $migrations = array_findAll ($migrations, MigrationStruct::status, MigrationStruct::DONE);
      if ($migrations) {
        $io->say ("    Updating the database...");
        try {
          $migrationsAPI->rollBack (0);
        }
        catch (\Exception $e) {
          $io->error ("Error while rolling back migrations: " . $e->getMessage ());
        }
        $io->say ("    <info>Done.</info>")->nl ();
      }
      else $io->comment ("    No reverse migrations were run.")->nl ();
    }
  }

  /**
   * Runs when module:refresh ends.
   * Override to implement additional functionality.
   */
  public function end ()
  {
    $this->io->nl ();
  }

  /**
   * (Re)publishes all module's public folders.
   */
  function publishModules ()
  {
    $this->unpublishModules ();

    $globalPublishDir = $this->app->modulesPublishingPath;
    $all              = $this->registry->getModules ();
    $links            = [];
    $isWindows        = strtoupper (substr (PHP_OS, 0, 3)) === 'WIN';

    foreach ($all as $module) {
      $pathToPublish = "$module->path/{$this->app->modulePublicPath}";
      if (file_exists ($pathToPublish)) {
        list ($folder, $name) = explode ('/', $module->name);
        $symlinkDir = "$globalPublishDir/$folder";
        if (!file_exists ($symlinkDir))
          mkdir ($symlinkDir, 0755, true);
        $symlinkFile = "$symlinkDir/$name";
        if (!$isWindows) {
          // On Mac or Linux use relative paths for symlinks.
          $relativeTarget = getRelativePath ("./$symlinkFile", "./$pathToPublish");
          symlink ($relativeTarget, $symlinkFile);
        }
        else {
          // Relative symlinks do not work properly on Windows, so use absolute paths.
          $pathToPublish = $this->app->baseDirectory . "/$pathToPublish";
          $symlinkFile   = $this->app->baseDirectory . "/$symlinkFile";
          // Create a junction instead of a symlink to avoid requiring administrator permissions.
          exec (sprintf ('mklink /j "%s" "%s"',
            str_replace ('/', '\\', $symlinkFile),
            str_replace ('/', '\\', $pathToPublish)));
        }
        $links[] = [$pathToPublish, $symlinkFile];
      }
    }
    return $links;
  }

  /**
   * Rebuilds the modules registration cache file, so that it correctly states the currently installed modules.
   */
  function rebuildRegistry ()
  {
    $subsystems = $this->loadModulesMetadata ($this->scanSubsystems (), ModuleInfo::TYPE_SUBSYSTEM);
    $plugins    = $this->loadModulesMetadata ($this->scanPlugins (), ModuleInfo::TYPE_PLUGIN);
    $private    = $this->loadModulesMetadata ($this->scanPrivateModules (), ModuleInfo::TYPE_PRIVATE);

    /** @var ModuleInfo[] $currentModules */
    $currentModules = array_merge ($subsystems, $plugins, $private);
    DependencySorter::sort ($currentModules);
    $currentModuleNames = self::getNames ($currentModules);

    $prevModules     = $this->registry->getModules ();
    $prevModuleNames = self::getNames ($prevModules);

    $newModuleNames = array_diff ($currentModuleNames, $prevModuleNames);
    $newModules     = self::getOnly ($newModuleNames, $currentModules);

    $moduleNamesKept = array_intersect ($currentModuleNames, $prevModuleNames);
    $moduleNamesKept = array_intersect ($moduleNamesKept,
      $this->registry->onlyPrivateOrPlugins ()->onlyEnabled ()->getModuleNames ());
    $modulesKept     = self::getOnly ($moduleNamesKept, $currentModules);

    $modules = [];
    foreach ($currentModules as $module) {
      /** @var ModuleInfo $oldModule */
      $oldModule = get ($prevModules, $module->name);
      if ($oldModule) {
        // Keep user preferences.
        foreach (ModuleInfo::KEEP_PROPS as $prop)
          $module->$prop = $oldModule->$prop;
      }
      $modules [$module->name] = $module;
    }
    $this->registry->setAllModules ($modules);
    $this->registry->save ();

    $this->publishModules ();

    if ($newModules || $modulesKept) {
      $this->registry->pendingInitializations (function () use ($newModules, $modulesKept) {
        $this->setupNewModules ($newModules);
        $this->updateModules ($modulesKept);
      });
    }

    $this->clearBootloaders ();

    $this->end ();
  }

  /**
   * @param string|ModuleInfo $module
   * @param bool              $migrate When true, runs the module's migrations, if any.
   */
  function setupModule ($module, $migrate = false)
  {
    if (is_string ($module))
      $module = $this->registry->getModule ($module);

    if ($migrate) {
      $databaseIsAvailable = Connection::getFromEnviroment ()->isAvailable ();
      $migrationsAPI       = $this->getMigrationsAPI ();
      $runMigrations       = $databaseIsAvailable && $migrationsAPI;

      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function setupNewModules (array $modules)
  {
    if (!$modules) return;
    $this->io->title ('Configuring New Modules');
    $this->setupModules ($modules);
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function updateModules (array $modules)
  {
    if (!$modules) return;
    $this->io->title ("Re-check Installed Modules");
    $this->setupModules ($modules);
  }

  /**
   * Deletes the currently generated bootloaders for all profiles.
   */
  private function clearBootloaders ()
  {
    $path = $this->app->getBootloadersPath ();
    if (fileExists ($path))
      rrmdir ($path);
    mkdir ("{$this->app->baseDirectory}/$path");
  }

  /**
   * @return MigrationsInterface
   */
  private function getMigrationsAPI ()
  {
    try {
      return $this->migrationsAPI
        ?: (($factory = $this->migrationsAPIFactory) ? $this->migrationsAPI = $factory() : null);
    }
    catch (InjectionException $e) {
      return null;
    }
  }

//  private function getSubsystemsOfProfile ()
//  {
//    return map ($this->profile->getSubsystems (), function ($moduleName) {
//      return (new ModuleInfo)->import ([
//        'name' => $moduleName,
//        'path' => "{$this->app->frameworkPath}/subsystems/$moduleName",
//      ]);
//    });
//  }

  private function loadModuleMetadata (ModuleInfo $module)
  {
    $composerJson = new JsonFile ("$module->path/composer.json");
    if ($composerJson->exists ()) {
      $composerJson->load ();
      $module->description = $composerJson->get ('description');
      $namespaces          = $composerJson->get ('autoload.psr-4');
      if ($namespaces) {
        $firstKey     = array_keys (get_object_vars ($namespaces))[0];
        $folder       = $namespaces->$firstKey;
        $bootstrapper = $module->getBootstrapperClass ();
        $filename     = str_replace ('\\', '/', $bootstrapper);
        $servicesPath = "$module->path/$folder/$filename.php";
        if (file_exists ($servicesPath))
          $module->bootstrapper = "$firstKey$bootstrapper";
        $rp = realpath ($module->path);
        if ($rp != "{$this->app->baseDirectory}/$module->path")
          $module->realPath = $rp;

        //load the dependencies to an array as detailed by the modules composer.json
        $module->dependencies = [];
        foreach ($composerJson->get ('require') as $dependencyName => $dependencyVersion)
          $module->dependencies[] = $dependencyName;
      }
    }
  }

  /**
   * @param ModuleInfo[] $modules
   * @param string       $type
   * @return \Electro\Core\Assembly\ModuleInfo[]
   */
  private function loadModulesMetadata (array $modules, $type)
  {
    foreach ($modules as $module) {
      $module->type = $type;
      $this->loadModuleMetadata ($module);
    }
    return $modules;
  }

  private function scanPlugins ()
  {
    return FilesystemFlow
      ::from ("{$this->app->baseDirectory}/{$this->app->pluginModulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (new ModuleInfo)->import ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $this->app->toRelativePath (normalizePath ($subDirInfo->getPathname ())),
            ]);
          });
      })
      ->all ();
  }

  private function scanPrivateModules ()
  {
    return FilesystemFlow
      ::from ("{$this->app->baseDirectory}/{$this->app->modulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (new ModuleInfo)->import ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $this->app->toRelativePath (normalizePath ($subDirInfo->getPathname ())),
            ]);
          });
      })
      ->all ();
  }

  /**
   * Returns all subsystems, irrespective of the configuration profile.
   *
   * @return ModuleInfo[]
   */
  private function scanSubsystems ()
  {
    return FilesystemFlow
      ::from ("{$this->app->frameworkPath}/subsystems")
      ->onlyDirectories ()
      ->map (function (SplFileInfo $dirInfo) {
        $path = normalizePath ($dirInfo->getPathname ());
        $p    = strpos ($path, 'framework/') + 9;
        return (new ModuleInfo)->import ([
          'name' => $dirInfo->getFilename (),
          'path' => $this->app->frameworkPath . substr ($path, $p),
        ]);
      })
      ->pack ()->all ();
  }

  private function setupModules (array $modules)
  {
    foreach ($modules as $module) {
      $this->io->writeln ("  <info>■</info> $module->name");
      $this->setupModule ($module, true);
    }
  }

  private function unpublishModules ()
  {
    $globalPublishDir = "{$this->app->baseDirectory}/{$this->app->modulesPublishingPath}";
    $dirs             = dirList ($globalPublishDir, DIR_LIST_DIRECTORIES, true);
    if ($dirs)
      foreach ($dirs as $dir)
        rrmdir ($dir);
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    $migrationsAPI = $this->getMigrationsAPI ();
    $migrations    = $migrationsAPI->module ($module->name)->status ();
    if ($migrations) {
      $io = $this->io;
      $io->comment ("    The module has migrations.");
      $migrations = array_findAll ($migrations, MigrationStruct::status, MigrationStruct::PENDING);
      if ($migrations) {
        $io->say ("    Updating the database...");
        try {
          $migrationsAPI->migrate ($module->name);
        }
        catch (\Exception $e) {
          $io->error ("Error while migrating: " . $e->getMessage ());
        }
        $io->say ("    <info>Done.</info>")->nl ();
      }
      else $io->comment ("    No new migrations to run.");
    }
  }

}
