<?php
namespace Electro\Kernel\Services;

use Auryn\InjectionException;
use Electro\ConsoleApplication\ConsoleApplication;
use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\Migrations\MigrationsInterface;
use Electro\Interfaces\ProfileInterface;
use Electro\Interop\MigrationStruct;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Lib\JsonFile;
use PhpKit\ExtPDO\Connection;
use PhpKit\Flow\FilesystemFlow;
use SplFileInfo;

/**
 * Manages modules installation, update and removal. It is also responsible for (re)building the registry.
 *
 * ><p>**Warning:** no validation of module names is performed on methods of this class. It is assumed this service is
 * only invoked for valid modules. Validation should be performed on the caller.
 */
class ModulesInstaller
{
  /**
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var ConsoleIOInterface
   */
  private $io;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
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

  function __construct (KernelSettings $kernelSettings, ConsoleApplication $consoleApp,
                        ModulesRegistry $modulesRegistry,
                        callable $migrationsAPIFactory, ProfileInterface $profile)
  {
    $this->kernelSettings       = $kernelSettings;
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
   * @param ModuleInfo[] $modules
   * @return ModuleInfo[]
   * @throws ConfigException
   */
  static private function modulesTopologicalSort (array $modules)
  {
    /** @var ModuleInfo[] $A All modules, indexed by name */
    $A = [];
    /** @var ModuleInfo[] $L The sorted list to be returned */
    $L = [];
    foreach ($modules as $module) {
      $A[$module->name] = $module;
      $module->tmp      = $module->requiredBy; // tmp is a temporary scratch list
    }
    /** @var ModuleInfo[] $S Set of all modules not dependend upon */
    $S = filter ($modules, function (ModuleInfo $m) { return !$m->requiredBy; }, true);
    while ($S) {
      $n = array_pop ($S);
      array_unshift ($L, $n);
      foreach ($n->dependencies as $name) {
        $m = $A[$name];
        if (in_array ($n->name, $m->tmp)) {
          $m->tmp = array_diff ($m->tmp, [$n->name]);
          if (!$m->tmp)
            $S[] = $m;
        }
      }
    }
    foreach ($modules as $m) {
      if ($m->tmp)
        throw new ConfigException(sprintf ('Cyclic dependency between modules: "%s" <-> "%s"', $m->name,
          implode ('" <-> "', $m->tmp)));
      unset ($m->tmp);
    }
    return $L;
  }

  /**
   * Sorts modules by the order they should be loaded, according to their dependencies.
   *
   * @param ModuleInfo[] $modules
   */
  static private function sortModules (array &$modules)
  {
    $types = [];
    foreach ($modules as $m)
      $types[ModuleInfo::TYPE_PRIORITY[$m->type]][] = $m;
    foreach ($types as &$t)
      $t = self::modulesTopologicalSort ($t);
    $modules = array_merge (...$types);
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

    $globalPublishDir = $this->kernelSettings->modulesPublishingPath;
    $all              = $this->registry->getModules ();
    $links            = [];
    $isWindows        = strtoupper (substr (PHP_OS, 0, 3)) === 'WIN';

    foreach ($all as $module) {
      $pathToPublish = "$module->path/{$this->kernelSettings->modulePublicPath}";
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
          $pathToPublish = $this->kernelSettings->baseDirectory . "/$pathToPublish";
          $symlinkFile   = $this->kernelSettings->baseDirectory . "/$symlinkFile";
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
    self::sortModules ($currentModules);
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
    $path = $this->kernelSettings->getBootloadersPath ();
    if (fileExists ($path))
      rrmdir ($path);
    mkdir ("{$this->kernelSettings->baseDirectory}/$path");
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
//        'path' => "{$this->kernelSettings->frameworkPath}/subsystems/$moduleName",
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
        if ($rp != "{$this->kernelSettings->baseDirectory}/$module->path")
          $module->realPath = $rp;

        $module->dependencies = array_diff (object_propNames ($composerJson->get ('require')), ['php']);
      }
    }
  }

  /**
   * @param ModuleInfo[] $modules
   * @param string       $type
   * @return ModuleInfo[]
   * @throws ConfigException
   */
  private function loadModulesMetadata (array $modules, $type)
  {
    /** @var ModuleInfo[] $all */
    $all = [];
    foreach ($modules as $module) {
      $module->type = $type;
      $this->loadModuleMetadata ($module);
      $all[$module->name] = $module;
    }
    // Filter out non-module package dependencies
    $allModuleNames = map ($modules, pluck ('name'));
    foreach ($modules as $module)
      $module->dependencies = array_values (array_intersect ($module->dependencies, $allModuleNames));

    foreach ($modules as $module) {
      foreach ($module->dependencies as $dep)
        if (isset($all[$dep]))
          $all[$dep]->requiredBy[] = $module->name;
        else throw new ConfigException("Invalid dependency: $dep");
    }

    return $modules;
  }

  private function scanPlugins ()
  {
    return FilesystemFlow
      ::from ("{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->pluginModulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (new ModuleInfo)->import ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => getRelativePathIfSubpath ($this->kernelSettings->baseDirectory, $subDirInfo->getPathname ()),
            ]);
          });
      })
      ->all ();
  }

  private function scanPrivateModules ()
  {
    return FilesystemFlow
      ::from ("{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->modulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (new ModuleInfo)->import ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => getRelativePathIfSubpath ($this->kernelSettings->baseDirectory, $subDirInfo->getPathname ()),
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
      ::from ("{$this->kernelSettings->frameworkPath}/subsystems")
      ->onlyDirectories ()
      ->map (function (SplFileInfo $dirInfo) {
        $path = normalizePath ($dirInfo->getPathname ());
        $p    = strpos ($path, 'framework/') + 9;
        return (new ModuleInfo)->import ([
          'name' => 'subsystems/' . $dirInfo->getFilename (),
          'path' => KernelSettings::FRAMEWORK_PATH . substr ($path, $p),
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
    $globalPublishDir = "{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->modulesPublishingPath}";
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
