<?php
namespace Electro\Core\Assembly\Services;

use Electro\Application;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Lib\JsonFile;
use Electro\Traits\InspectionTrait;

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

  static private function hidrateModulesList (array $data)
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
   * @return \Electro\Core\Assembly\ModuleInfo[]
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
   *
   * @return bool false if the registry file doesn't exist.
   */
  function load ()
  {
    $json = new JsonFile ($this->getRegistryPath (), true);
    if ($json->exists ()) {
      $this->importFrom ($json->load ()->data);
      return true;
    }
    return false;
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
   * Sets information about all registered modules.
   *
   * @param ModuleInfo[] $modules A map of module names to module information objects.
   */
  function setAllModules (array $modules)
  {
    $this->modules = $modules;
  }

  /**
   * Removes a module from the registry. The module's files will not be affected.
   *
   * @param string $moduleName
   * @return bool false if the module name does not match an installed module, or if it is a subsystem module.
   */
  function unregisterModule ($moduleName)
  {
    $module = $this->getModule ($moduleName);
    if (!$module || $this->isSubsystem ($moduleName)) return false;
    unset ($this->modules[$moduleName]);
    $this->save ();
    return true;
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

}
