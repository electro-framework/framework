<?php
namespace Selenia;

class ModulesRegistry
{
  const ref = __CLASS__;

  /**
   * @var ModuleInfo[]
   */
  public $subsystems = [];

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
}
