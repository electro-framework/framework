<?php
namespace Selenia;

use PhpKit\Flow\Flow;

class ModulesRegistry
{
  const ref = __CLASS__;
  /**
   * @var ModuleInfo[]
   */
  public $plugins = [];
  /**
   * @var ModuleInfo[]
   */
  public $projectModules = [];
  /**
   * An array of service provider class names. These will be loaded sequentially on application
   * bootstrap.
   * @var string[]
   */
  public $serviceProviders = [];
  /**
   * @var ModuleInfo[]
   */
  public $subsystems = [];

  private static function hidrateModulesList (array $data)
  {
    return map ($data, function ($o) { return array_toClass ($o, ModuleInfo::ref); });
  }

  /**
   * Gests an iterator that iterates all modules.
   * @return Flow
   */
  function all ()
  {
    return Flow::sequence ([$this->subsystems, $this->plugins, $this->projectModules])->reindex ();
  }

  /**
   * Gests an iterator that iterates all modules.
   * @return Flow
   */
  function allNonCoreModules ()
  {
    return $this->allNonCoreSeq ()->reindex ();
  }

  function getPathMappings ()
  {
    return $this->allNonCoreSeq ()->mapAndFilter (function (ModuleInfo $mod, &$k) {
      $k = $mod->realPath;
      return $mod->realPath ? $mod->path : null;
    })->all ();
  }

  /**
   * Imports an array representation of an instance of this class (possibly generated from {@see json_decode()}) into
   * the instance's public properties.
   * @param array $data
   * @return $this
   */
  function importFrom (array $data)
  {
    $this->plugins        = ($l = get ($data, 'plugins')) ? self::hidrateModulesList ($l) : [];
    $this->projectModules = ($l = get ($data, 'projectModules')) ? self::hidrateModulesList ($l) : [];
    $this->subsystems     = ($l = get ($data, 'subsystems')) ? self::hidrateModulesList ($l) : [];

    $this->serviceProviders = get ($data, 'serviceProviders');
    return $this;
  }

  private function allNonCoreSeq ()
  {
    return Flow::sequence ([$this->plugins, $this->projectModules]);
  }
}
