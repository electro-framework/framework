<?php
namespace Selenia\Core\Assembly\Services;
use Selenia\Application;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;

/**
 * Provides an API for managing the application's modules.
 */
class ModulesManager
{
  const ref = __CLASS__;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var ModuleServices
   */
  private $moduleServices;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;

  /**
   * @param InjectorInterface $injector
   * @param Application       $app
   * @param ModulesRegistry   $modulesRegistry
   * @param ModuleServices    $moduleServices
   */
  function __construct (InjectorInterface $injector, Application $app, ModulesRegistry $modulesRegistry,
                        ModuleServices $moduleServices)
  {
    $this->app             = $app;
    $this->injector        = $injector;
    $this->moduleServices  = $moduleServices;
    $this->modulesRegistry = $modulesRegistry;
  }

  /**
   * Initializes all modules.
   *
   * @throws ConfigException
   */
  function bootModules ()
  {
    /** @var ModuleInterface[] $providers */
    $providers = [];
    /** @var string[] $paths */
    $paths = [];

    // Providers registration phase

    foreach ($this->modulesRegistry->getAllModules () as $name => $module) {
      if ($module->enabled && $module->serviceProvider) {
        $provider = new $module->serviceProvider;

        if ($provider instanceof ServiceProviderInterface)
          $provider->register ($this->injector);

        if ($provider instanceof ModuleInterface) {
          $providers[] = $provider;
          $paths[]     = $module->path;
        }
      }
    }

    // Providers configuration phase

    foreach ($providers as $i => $provider) {
      $this->moduleServices->setPath ($paths[$i]);
      $provider->configure ($this->moduleServices);
    }
    $this->moduleServices->runPostConfig ();

    // Providers boot phase

    foreach ($providers as $provider) {
      $this->injector->execute ([$provider, 'boot']);
    }
  }

}
