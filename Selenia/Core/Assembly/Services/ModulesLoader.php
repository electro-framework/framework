<?php
namespace Selenia\Core\Assembly\Services;

use Exception;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
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
    /** @var ModuleInfo[] $providerModules */
    $providerModules = [];

    // Providers registration phase

    foreach ($this->modulesRegistry->getAllModules () as $name => $module) {
      if ($module->enabled && $module->bootstrapper) {
        if (!class_exists ($module->bootstrapper)) {
          $this->logModuleError ($module, "Class <kbd>$module->bootstrapper</kbd> was not found.");
          continue; // don't load this module.
        }
        try {
          $provider = new $module->bootstrapper;
          if ($provider instanceof ServiceProviderInterface)
            $provider->register ($this->injector);

          if ($provider instanceof ModuleInterface) {
            $providers[]       = $provider;
            $paths[]           = $module->path;
            $providerModules[] = $module;
          }
          if ($module->errorStatus) {
            $module->errorStatus = null;
            $this->modulesRegistry->save ();
          }
        }
        catch (Exception $e) {
          $this->logModuleError ($module, $e->getMessage (), $e);
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
      if (is_callable ($fn))
        try {
          $this->injector->execute ($fn);
        }
        catch (Exception $e) {
          $this->logModuleError ($providerModules[$i], $e->getMessage (), $e);
        }
    }
    $moduleServices->runPostConfig ();

    // Providers boot phase

    foreach ($providers as $i => $provider) {
      $fn = [$provider, 'boot'];
      if (is_callable ($fn))
        try {
          $this->injector->execute ($fn);
        }
        catch (Exception $e) {
          $this->logModuleError ($providerModules[$i], $e->getMessage (), $e);
        }
    }
  }

  private function logModuleError (ModuleInfo $module, $message, Exception $e = null)
  {
    $module->errorStatus = $message;
    _log()->error ($message, $e ? ['trace' => $e->getTrace ()] : []);
    // Only save errorStatus if not on production, otherwise concurrency problems while saving might occur.
    if ($this->app->debugMode)
      $this->modulesRegistry->save ();
  }

}
