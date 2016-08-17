<?php
namespace Electro\Core\Assembly\Services;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModuleInterface;
use Exception;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Log\LoggerInterface;

/**
 * Loads and initializes the application's modules.
 */
class ModulesLoader
{
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
   * @param ModulesRegistry   $modulesRegistry
   * @param LoggerInterface   $logger
   */
  function __construct (InjectorInterface $injector, ModulesRegistry $modulesRegistry, LoggerInterface $logger)
  {
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

//stepProfiling("Begin Providers registration");
    foreach ($this->modulesRegistry->onlyBootable ()->onlyEnabled ()->getModules () as $name => $module) {
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
//stepProfiling("Provider $name done");
      }
      catch (Exception $e) {
        $this->logModuleError ($module, $e->getMessage (), $e);
      }
    }

    // Module configuration phase

    // Warning: this MUST NOT be injected on the constructor above!
    // This is a shared service.
    /** @var ModuleServices $moduleServices */
    $moduleServices = $this->injector->make (ModuleServices::class);

//stepProfiling("Begin Module configuration phase");
    foreach ($providers as $i => $provider) {
      $moduleServices->setPath ($paths[$i]);
      $fn = [$provider, 'configure'];
      if (is_callable ($fn))
        try {
          $this->injector->execute ($fn);
//$m = str_segmentsLast($paths[$i], '/');
//stepProfiling("Module $m configured");
        }
        catch (Exception $e) {
          $this->logModuleError ($providerModules[$i], $e->getMessage (), $e);
        }
    }

    // From this point on, errors are not suppressed, as all modules are already loaded and initialized.

    $moduleServices->runPostConfig ();
//stepProfiling("Post config runned");

    // Providers boot phase

//stepProfiling("Begin Providers boot phase");
    foreach ($providers as $i => $provider) {
      // Only boot modules that have not failed the initialization process.
      if (!$providerModules[$i]->errorStatus) {
        $fn = [$provider, 'boot'];
        if (is_callable ($fn))
          try {
            $this->injector->execute ($fn);
//$m = str_segmentsLast($paths[$i], '/');
//stepProfiling("Module $m booted");
          }
          catch (Exception $e) {
            $this->logModuleError ($providerModules[$i], $e->getMessage (), $e);
          }
      }
    }
//stepProfiling("bootModules complete");

    // Pending module installation/update initializations

    $extraInit = $this->modulesRegistry->pendingInitializations ();
    if ($extraInit)
      $extraInit ();
  }

  private function logModuleError (ModuleInfo $module, $message, Exception $e = null)
  {
    if (!$e)
      $e = new \RuntimeException ($message);
    // Make sure the exception gets logged before throwing it
    Debug::logException ($this->logger, $e);

    throw $e;
  }

}
