<?php
namespace Electro\Core\Assembly\Services;

use Electro\Core\Assembly\Config\AssemblyModule;
use Electro\Core\Logging\Config\LoggingModule;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Traits\EventEmitterTrait;
use Exception;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Log\LoggerInterface;

/**
 * The service that bootstraps the framework and the application.
 *
 * <p>Modules should use this service to subscribe to bootstrap events (see the Bootstrapper::EVENT_XXX constants).
 */
class Bootstrapper
{
  use EventEmitterTrait;

  /**
   * This event type occurs during the normal bootstrapping process to allow modules to register their services and
   * perform other initializations.
   * <p>PSR-7 HTTP related objects are initalized at this stage and a module may inspect the HTTP request to decide
   * if it should boot or not or, for partial booting, which initializations to perform.
   * <p>This event occurs on an order determined by module dependencies, so all services required by a module should be
   * available at the time this event reaches it.
   * >**Note:** only initialization stuff should be performed during this phase, other kinds of processing SHOULD be
   * performed later on the processing pipeline.
   */
  const EVENT_BOOT = 1;
  /**
   * This event type occurs after all modules are initialized, so all services should have been registered and be
   * available at that time. Use this event for performing additional initialization steps.
   * >**Note:** use this event sparingly.
   * ><p>The {@see Bootstrapper::EVENT_BOOT} event occurs in an order determined by module dependencies, which
   * obviates the need to postpone initialization in most cases.
   */
  const EVENT_POST_BOOT = 2;
  /**
   * This event type occurs before the PSR-7 HTTP related objects are initalized, therefore the listeners able to
   * override the HTTP processing subsystem with their own implementation.
   * <p>The event can also be used when a module needs to perform some action before the main bootstrapping process
   * begins and the other modules are initialized.
   * >**Note:** use this event sparingly.
   */
  const EVENT_PRE_BOOT = 0;
  /**
   * @var InjectorInterface
   */
  private $injector;

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  function run ()
  {
    /*
     * Boot up the core framework modules.
     *
     * This occurs before the framework's main boot up sequence.
     * Unlike the later, which is managed automatically, this pre-boot process is manually defined and consists of just
     * a few core services that must be setup before any other module loads.
     */
    $this->injector->execute ([LoggingModule::class, 'register']);

    try {
      $this->injector->execute ([AssemblyModule::class, 'register']);

      /**
       * Load all remaining modules, allowing them to subscribe to bootstrap events.
       */

      $registry = $this->injector->make (ModulesRegistry::class);
      foreach ($registry->onlyBootable ()->onlyEnabled ()->getModules () as $name => $module) {
        $modBoot = $module->bootstrapper;
        if (!class_exists ($modBoot)) // don't load this module.
          $this->logModuleError ("Class <kbd>$modBoot</kbd> was not found.");
        elseif (is_a ($modBoot, ModuleInterface::class))
          $modBoot::boot ($this);
        //else ignore the module
      }

      /**
       * Boot up all non-core modules.
       */

      $this->emitAndInject (self::EVENT_PRE_BOOT);
      $this->emitAndInject (self::EVENT_BOOT);
      $this->emitAndInject (self::EVENT_POST_BOOT);
    }
    catch (Exception $e) {
      $this->logModuleError ($e->getMessage (), $e);
    }

  }

  /**
   * Emits an event to all handlers registered to that event (if any), injecting the arguments to each calling handler.
   *
   * @param string $event The event name.
   */
  protected function emitAndInject ($event)
  {
    foreach (get ($this->listeners, $event, []) as $l)
      $this->injector->execute ($l);
  }

  private function logModuleError ($message, Exception $e = null)
  {
    if (!$e)
      $e = new \RuntimeException ($message);
    // Make sure the exception gets logged before throwing it.
    // Note: the logger is lazily created to allow some module to override it before this error occurs.
    Debug::logException ($this->injector->make (LoggerInterface::class), $e);

    throw $e;
  }

}
