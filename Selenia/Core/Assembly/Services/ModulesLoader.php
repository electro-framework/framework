<?php
namespace Selenia\Core\Assembly\Services;

use Exception;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\ModuleInterface;

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
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;

  /**
   * @param InjectorInterface $injector
   * @param Application       $app
   * @param ModulesRegistry   $modulesRegistry
   * @param LoggerInterface   $logger
   */
  function __construct (InjectorInterface $injector, Application $app, ModulesRegistry $modulesRegistry,
                        LoggerInterface $logger)
  {
    $this->app             = $app;
    $this->injector        = $injector;
    $this->modulesRegistry = $modulesRegistry;
    $this->logger          = $logger;
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
          // Clear module's previous error status (if any)
          if ($module->errorStatus) {
            $module->errorStatus = null;
            $this->modulesRegistry->save (); // Note: this only occurs on debug mode.
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
    /** @var ModuleServices $moduleServices */
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

    // From this point on, errors are not suppressed, as all modules are already loaded and initialized.

    $moduleServices->runPostConfig ();

    // Providers boot phase

    foreach ($providers as $i => $provider) {
      // Only boot modules that have not failed the initialization process.
      if (!$providerModules[$i]->errorStatus) {
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
  }

  private function logModuleError (ModuleInfo $module, $message, Exception $e = null)
  {
    if (!$e)
      $e = new \RuntimeException ($message);
    // Errors on subsystem modules are considered to be always fatal.
    if ($module->type == ModuleInfo::TYPE_SUBSYSTEM)
      throw $e;

    $module->errorStatus = $message;
    Debug::logException ($this->logger, $e);
    // Only save errorStatus if not on production, otherwise concurrency problems while saving might occur.
    if ($this->app->debugMode)
      $this->modulesRegistry->save ();
  }

}
