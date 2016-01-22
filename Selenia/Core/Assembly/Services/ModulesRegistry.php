<?php
namespace Selenia\Core\Assembly\Services;

use PhpKit\Flow\FilesystemFlow;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Lib\JsonFile;
use Selenia\Traits\InspectionTrait;
use SplFileInfo;

/**
 * Represents the modules' registry.
 * It is serialized on disk on the `private/modules/registry.json` file.
 */
class ModulesRegistry
{
  use InspectionTrait;

  static $INSPECTABLE = ['modules'];
  /**
   * @var Application
   */
  private $app;
  /**
   * Contains information about all registered modules.
   * <p>It's a map of module names to module information objects.
   *
   * @var ModuleInfo[]
   */
  private $modules = [];

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  private static function hidrateModulesList (array $data)
  {
    return map ($data, function ($o) { return array_toClass ($o, ModuleInfo::class); });
  }

  function getAllModuleNames ()
  {
    return map ($this->modules, function (ModuleInfo $m) { return $m->name; });
  }

  /**
   * Gets information about all registered modules.
   * <p>Returns a map of module names to module information objects.
   *
   * @return ModuleInfo[]
   */
  function getAllModules ()
  {
    return $this->modules;
  }

  /**
   * Returns the names of all installed plugins and private modules, in that order.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return string[]
   */
  function getApplicationModuleNames ($onlyEnabled = false)
  {
    return array_merge ($this->getPluginNames ($onlyEnabled), $this->getPrivateModuleNames ($onlyEnabled));
  }

  /**
   * Gets a list of all plugins and private modules, in that order.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return \Selenia\Core\Assembly\ModuleInfo[]
   */
  function getApplicationModules ($onlyEnabled = false)
  {
    return array_merge ($this->getPlugins ($onlyEnabled), $this->getPrivateModules ($onlyEnabled));
  }

  /**
   * Gets the module information object for the module registered with the given name.
   *
   * @param string $moduleName vendor-name/product-name
   * @return ModuleInfo|null `null` if the module is not registered.
   */
  function getModule ($moduleName)
  {
    return get ($this->modules, $moduleName);
  }

  function getPathMappings ()
  {
    return mapAndFilter ($this->getApplicationModules (), function (ModuleInfo $mod, &$k) {
      $k = $mod->realPath;
      return $mod->realPath ? $mod->path : null;
    });
  }

  /**
   * Returns the names of all registered modules of tyoe 'plugin'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return string[]
   */
  function getPluginNames ($onlyEnabled = false)
  {
    return mapAndFilter (array_values ($this->modules),
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_PLUGIN && ($m->enabled || !$onlyEnabled) ? $m->name : null;
      });
  }

  /**
   * Returns a list of module infomation objects for all registered modules of tyoe 'plugin'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return ModuleInfo[]
   */
  function getPlugins ($onlyEnabled = false)
  {
    return array_filter ($this->modules,
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_PLUGIN && ($m->enabled || !$onlyEnabled);
      });
  }

  /**
   * Returns the names of all registered modules of tyoe 'private'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return string[]
   */
  function getPrivateModuleNames ($onlyEnabled = false)
  {
    return mapAndFilter (array_values ($this->modules),
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_PRIVATE && ($m->enabled || !$onlyEnabled) ? $m->name : null;
      });
  }

  /**
   * Returns a list of module infomation objects for all registered modules of tyoe 'private'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return ModuleInfo[]
   */
  function getPrivateModules ($onlyEnabled = false)
  {
    return array_filter ($this->modules,
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_PRIVATE && ($m->enabled || !$onlyEnabled);
      });
  }

  /**
   * Returns the names of all registered modules of tyoe 'subsystem'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return string[]
   */
  function getSubsystemNames ($onlyEnabled = false)
  {
    return mapAndFilter (array_values ($this->modules),
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_SUBSYSTEM && ($m->enabled || !$onlyEnabled) ? $m->name : null;
      });
  }

  /**
   * Returns a list of module infomation objects for all registered modules of tyoe 'subsystem'.
   *
   * @param bool $onlyEnabled Return only modules that are enabled.
   * @return ModuleInfo[]
   */
  function getSubsystems ($onlyEnabled = false)
  {
    return array_filter ($this->modules,
      function (ModuleInfo $m) use ($onlyEnabled) {
        return $m->type == ModuleInfo::TYPE_SUBSYSTEM && ($m->enabled || !$onlyEnabled);
      });
  }

  /**
   * Imports an array representation of an instance of this class (possibly generated from {@see json_decode()}) into
   * the instance's public properties.
   *
   * @param array $data
   * @return $this
   */
  function importFrom (array $data)
  {
    $this->modules = isset($data['modules']) ? self::hidrateModulesList ($data['modules']) : [];
    return $this;
  }

  /**
   * Checks if a module is installed.
   *
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isInstalled ($moduleName)
  {
    return isset ($this->modules[$moduleName]);
  }

  /**
   * Checks if the installed module with the given name is a plugin.
   *
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isPlugin ($moduleName)
  {
    $mod = get ($this->modules, $moduleName);
    return $mod ? $mod->type == ModuleInfo::TYPE_PLUGIN : false;
  }

  /**
   * Checks if the installed module with the given name is a private application module.
   *
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isPrivateModule ($moduleName)
  {
    $mod = get ($this->modules, $moduleName);
    return $mod ? $mod->type == ModuleInfo::TYPE_PRIVATE : false;
  }

  /**
   * Checks if the installed module with the given name is a framework core module (subsystem).
   *
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isSubsystem ($moduleName)
  {
    $mod = get ($this->modules, $moduleName);
    return $mod ? $mod->type == ModuleInfo::TYPE_SUBSYSTEM : false;
  }

  /**
   * Loads the modules registration configuration for this project.
   */
  function load ()
  {
    $json = new JsonFile ($this->getRegistryPath (), true);
    if ($json->exists ())
      $this->importFrom ($json->load ()->data);
    else {
      $this->rebuildRegistry ();
    }
  }

  /**
   * @return ModulesRegistry
   */
  function rebuildRegistry ()
  {
    $subsystems = $this->loadModulesMetadata ($this->scanSubsystems (), ModuleInfo::TYPE_SUBSYSTEM);
    $plugins    = $this->loadModulesMetadata ($this->scanPlugins (), ModuleInfo::TYPE_PLUGIN);
    $private    = $this->loadModulesMetadata ($this->scanPrivateModules (), ModuleInfo::TYPE_PRIVATE);
    $main       = $this->makeMainModule ();
    $this->loadModuleMetadata ($main);
    /** @var ModuleInfo[] $all */
    $all = array_merge ([$main], $subsystems, $plugins, $private);

    $oldModules    = $this->modules;
    $this->modules = [];
    foreach ($all as $module) {
      /** @var ModuleInfo $oldModule */
      $oldModule = get ($oldModules, $module->name);
      if ($oldModule) {
        // Keep user preferences.
        $module->enabled = $oldModule->enabled;
      }
      $this->modules [$module->name] = $module;
    }
    $this->save ();
  }

  /**
   * Updates this instance and also the registration cache file, so that it correctly states the currently installed
   * modules.
   */
  function refresh ()
  {
    // Delete existing registry (if any) to completely discard all information.
    unlink ($this->getRegistryPath ());
    // Now build a fresh new registry.
    $this->rebuildRegistry ();
  }

  /**
   * Saves the modules registration configuration for this project.
   */
  function save ()
  {
    $filePath = $this->getRegistryPath ();
    $path     = dirname ($filePath);
    if (!file_exists ($path))
      mkdir ($path, 0777, true);
    $json = new JsonFile ($filePath, true);
    $json->assign (['modules' => $this->modules])->save ();
  }

  /**
   * Checks if the given name is a valid module name.
   *
   * @param string $name A module name in `vendor-name/package-name` format.
   * @return bool `true` if the name is valid.
   */
  function validateModuleName ($name)
  {
    return (bool)preg_match ('#^[a-z0-9\-]+/[a-z0-9\-]+$#', $name);
  }

  /**
   * @return string
   */
  private function getRegistryPath ()
  {
    return "{$this->app->storagePath}/modules.json";
  }

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
      }
    }
  }

  /**
   * @param ModuleInfo[] $modules
   * @return ModuleInfo[]
   */
  private function loadModulesMetadata (array $modules, $type)
  {
    foreach ($modules as $module) {
      $module->type = $type;
      $this->loadModuleMetadata ($module);
    }
    return $modules;
  }

  private function makeMainModule ()
  {
    return (new ModuleInfo)->_assign ([
      'name'         => 'app-kernel',
      'description'  => 'Application kernel',
      'path'         => 'private/app-kernel',
      'bootstrapper' => 'AppKernel\Config\AppKernelModule',
      'type'         => ModuleInfo::TYPE_SUBSYSTEM,
    ]);
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
            return (new ModuleInfo)->_assign ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $this->app->toRelativePath ($subDirInfo->getPathname ()),
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
            return (new ModuleInfo)->_assign ([
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $this->app->toRelativePath ($subDirInfo->getPathname ()),
            ]);
          });
      })
      ->all ();
  }

  private function scanSubsystems ()
  {
    return FilesystemFlow
      ::from ("{$this->app->frameworkPath}/subsystems")
      ->onlyDirectories ()
      ->map (function (SplFileInfo $dirInfo) {
        $path = $dirInfo->getPathname ();
        $p    = strpos ($path, 'framework/');
        return (new ModuleInfo)->_assign ([
          'name' => $dirInfo->getFilename (),
          'path' => "private/packages/selenia/" . substr ($path, $p),
        ]);
      })
      ->pack ()->all ();
  }

}
