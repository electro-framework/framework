<?php
namespace Selenia\Core\Assembly\Services;
use Selenia\Application;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;

/**
 * Loads and initializes the application's modules.
 */
class ModulesLoader
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;

  /**
   * @param InjectorInterface $injector
   * @param Application       $app
   * @param ModulesRegistry   $modulesRegistry
   */
  function __construct (InjectorInterface $injector, Application $app, ModulesRegistry $modulesRegistry)
  {
    $this->app             = $app;
    $this->injector        = $injector;
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
      if ($module->enabled && $module->bootstrapper) {
        $provider = new $module->bootstrapper;

        if ($provider instanceof ServiceProviderInterface)
          $provider->register ($this->injector);

        if ($provider instanceof ModuleInterface) {
          $providers[] = $provider;
          $paths[]     = $module->path;
        }
      }
    }

    // Module configuration phase

    // Warning: this MUST NOT be injected on the constructor above!
    // This is a shared service.
    $moduleServices = $this->injector->make (ModuleServices::class);

    foreach ($providers as $i => $provider) {
      $moduleServices->setPath ($paths[$i]);
      $fn = [$provider, 'configure'];
      if (is_callable($fn))
        $this->injector->execute($fn);
    }
    $moduleServices->runPostConfig ();

    // Providers boot phase

    foreach ($providers as $provider) {
      $fn = [$provider, 'boot'];
      if (is_callable($fn))
        $this->injector->execute($fn);
    }
  }

}
