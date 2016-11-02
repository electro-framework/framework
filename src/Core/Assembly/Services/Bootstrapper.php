<?php
namespace Electro\Core\Assembly\Services;

use Electro\Core\Assembly\Config\AssemblyModule;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Logging\Config\LoggingModule;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\ProfileInterface;
use Electro\Traits\EventEmitterTrait;
use Exception;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Log\LoggerInterface;

/**
 * Use this event for overriding core framework services.
 *
 * <p>This event type occurs once, before the PSR-7 HTTP related objects are initalized, therefore the listeners are
 * able to override the HTTP processing subsystem with their own implementation.
 * <p>The event can also be used when a module needs to perform some action before the main bootstrapping process
 * begins and the other modules are initialized.</p>
 * ### Arguments:
 * > `(InjectorInterface $injector)`
 *
 * ### Notes:
 * >Use this event sparingly.
 */
const PRE_REGISTER = 0;
/**
 * Use this event for registering a module's services on the injector.
 *
 * <p>When hanling this event, a module SHOULD refrain from doing anything else, both to allow other modules to
 * override the services just registered and to prevent invalid references to services that are not registered yet.
 * ><p>It is a common scenario for a module to register a listener for both  this event and the
 * {@see Bootstrapper::EVENT_BOOT} event, defining a service on the former and injecting it on the later, giving a
 * chance to other modules for overriding it.</p>
 * ><p>This event type occurs once, right after the PSR-7 HTTP related objects are initalized, so modules are able to
 * inspect the HTTP request for deciding what to register.
 * ### Arguments:
 * > `(InjectorInterface $injector)`
 */
const REGISTER_SERVICES = 1;
/**
 * Use this event for configuring services.
 *
 * <p>This event type occurs once, during the normal bootstrapping process, to allow modules to make use of services
 * previously registered on the injector to perform configurations/initializations on them.
 * <p>PSR-7 HTTP related objects are already initialized at this stage and a module may inspect the HTTP request to
 * decide if it should boot or not or, for partial booting, which initializations to perform.
 * <p>This event occurs on an order determined by module dependencies, so all services required by a module should be
 * available at the time this event reaches it.</p>
 * ### Arguments:
 * > `(mixed ...$args)` - use any injectable arguments you need.
 *
 * ### Notes:
 * > Only initialization/configuration operations should be performed during this phase, other kinds of processing
 * SHOULD be performed later on the processing pipeline.
 */
const CONFIGURE = 2;
/**
 * Use this event for performing additional initialization/configuration steps.
 *
 * <p>This event type occurs once, after all modules are initialized, so all services should have been registered and
 * be available at that time.
 * ### Arguments:
 * > `(mixed ...$args)` - use any injectable arguments you need.
 *
 * ### Notes:
 * ><p>Use this event sparingly.</p>
 * ><p>The {@see Bootstrapper::EVENT_BOOT} event occurs in an order determined by module dependencies, which
 * obviates the need to postpone initialization in most cases.
 */
const RECONFIGURE = 3;

/**
 * The service that bootstraps the framework and the application.
 *
 * <p>Modules should use this service to subscribe to bootstrap events (see the Bootstrapper::EVENT_XXX constants).
 */
class Bootstrapper
{
  use EventEmitterTrait;

  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var ProfileInterface
   */
  public $profile;

  function __construct (InjectorInterface $injector, ProfileInterface $profile)
  {
    $this->injector = $injector;
    $this->profile  = $profile;
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

      /*
       * Load all remaining modules, allowing them to subscribe to bootstrap events.
       */
      $exclude    = array_flip ($this->profile->getExcludedModules ());
      $subsystems = array_flip ($this->profile->getSubsystems ());

      /** @var ModulesRegistry $registry */
      $registry = $this->injector->make (ModulesRegistry::class);

      foreach ($registry->onlyBootable ()->onlyEnabled ()->getModules () as $name => $module) {
        /** @var ModuleInfo $module */
        if (isset ($exclude[$module->name]) ||
            ($module->type == ModuleInfo::TYPE_SUBSYSTEM && !isset($subsystems[$module->name]))
        ) continue;
        $modBoot = $module->bootstrapper;
        if (!class_exists ($modBoot)) // don't load this module.
          $this->logModuleError ("Class <kbd>$modBoot</kbd> was not found.");
        elseif (is_a ($modBoot, ModuleInterface::class, true))
          $modBoot::bootUp ($this, $module);
        //else ignore the module
      }

      /**
       * Boot up all non-core modules.
       */

      $this->emit (PRE_REGISTER, $this->injector);
      $this->emit (REGISTER_SERVICES, $this->injector);
      $this->emitAndInject (CONFIGURE);
      $this->emitAndInject (RECONFIGURE);
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
