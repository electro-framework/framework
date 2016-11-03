<?php
namespace Electro\Kernel\Services;

use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Lib\JsonFile;
use Electro\Traits\InspectionTrait;

/**
 * Represents the central modules registry, which contains information about all known framework subsystems, plugins and
 * application private modules.
 *
 * It is serialized on disk on the `private/modules/registry.json` file.
 */
class ModulesRegistry
{
  use InspectionTrait;

  static $INSPECTABLE = ['modules'];
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * When set, module retrieval methods will return only modules that pass all filtering callbacks.
   * <p>Callback syntax: `function (ModuleInfo $module):bool`
   *
   * @var string|null
   */
  private $moduleFilters = [];
  /**
   * Contains information about all registered modules.
   * <p>It's a map of module names to module information objects.
   *
   * @var ModuleInfo[]
   */
  private $modules = [];
  /**
   * @var callable A callback set by {@see ModulesInstaller} if it has run and there are possibly pending
   *      initializations to be performed on some modules.
   */
  private $pendingInit = false;

  function __construct (KernelSettings $kernelSettings)
  {
    $this->kernelSettings = $kernelSettings;
  }

  /**
   * Checks if the given name is a valid module name.
   *
   * @param string $name A module name in `vendor-name/package-name` format.
   * @return bool `true` if the name is valid.
   */
  static public function validateModuleName ($name)
  {
    return (bool)preg_match ('#^[a-z0-9\-]+/[a-z0-9\-]+$#', $name);
  }

  static private function hidrateModulesList (array $data)
  {
    return map ($data, function ($o) { return array_toClass ($o, ModuleInfo::class); });
  }

  /**
   * Clears all conditions for module retrieval.
   *
   * @return $this
   */
  function all ()
  {
    $this->moduleFilters = [];
    return $this;
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

  /**
   * Retrieves the names of all modules that match the previously set conditions (if any).
   *
   * @return string[]
   */
  function getModuleNames ()
  {
    return array_values (map ($this->getModules (), function (ModuleInfo $m) { return $m->name; }));
  }

  /**
   * Retrieves all modules that match the previously set conditions (if any).
   * <p>Returns a map of module names to module information objects.
   *
   * @return ModuleInfo[]
   */
  function getModules ()
  {
    $modules = filter ($this->modules, function (ModuleInfo $m) {
      foreach ($this->moduleFilters as $filter)
        if (!$filter($m))
          return false;
      return true;
    });
    $this->all ();
    return $modules;
  }

  function getPathMappings ()
  {
    return mapAndFilter ($this->onlyPrivateOrPlugins ()->onlyEnabled ()->getModules (),
      function (ModuleInfo $mod, &$k) {
        $k = $mod->realPath;
        return $mod->realPath ? $mod->path : null;
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
    $json = new JsonFile ($this->getRegistryPath (), true, true);
    if ($json->exists ()) {
      $this->importFrom ($json->load ()->data);
      return true;
    }
    return false;
  }

  /**
   * Adds a custom condition for module retrieval.
   * <p>Callback syntax: `function (ModuleInfo $module):bool`
   *
   * @param callable|null $filter If null, no filter will be added.
   * @return $this
   */
  function only (callable $filter = null)
  {
    if ($filter)
      $this->moduleFilters[] = $filter;
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyBootable ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return (bool)$module->bootstrapper; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyDisabled ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return !$module->enabled; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyEnabled ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return $module->enabled; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyNotBootable ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return !$module->bootstrapper; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyPlugins ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return $module->type == ModuleInfo::TYPE_PLUGIN; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyPrivate ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return $module->type == ModuleInfo::TYPE_PRIVATE; };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlyPrivateOrPlugins ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) {
      return $module->type == ModuleInfo::TYPE_PRIVATE ||
             $module->type == ModuleInfo::TYPE_PLUGIN;
    };
    return $this;
  }

  /**
   * Adds a condition for module retrieval.
   *
   * @return $this
   */
  function onlySubsystems ()
  {
    $this->moduleFilters[] = function (ModuleInfo $module) { return $module->type == ModuleInfo::TYPE_SUBSYSTEM; };
    return $this;
  }

  /**
   * Shortcut method to get the file system path of a module's root folder, relative to the project's root folder.
   *
   * @param string $moduleName vendor-name/product-name
   * @return string The module's path.
   */
  function pathOf ($moduleName)
  {
    return $this->getModule ($moduleName)->path;
  }

  /**
   * Sets or gets a callback to perform pending module installation/update initializations after all modules were
   * loaded.
   *
   * <p>This occurs when {@see ModulesInstaller} has run and new modules have been installed or existing modules were
   * updated.
   *
   * @param callable|null $value [optional] If specified, sets the callback to be invoked later.
   * @return callable|null The current value or the value being set.
   */
  function pendingInitializations ($value = null)
  {
    return isset($value) ? $this->pendingInit = $value : $this->pendingInit;
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
   * @return string
   */
  private function getRegistryPath ()
  {
    return "{$this->kernelSettings->storagePath}/modules.json";
  }

}
