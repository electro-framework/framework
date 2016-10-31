<?php
namespace Electro\Core\Assembly\Services;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Traits\ObserverTrait;

/**
 * A service that
 */
class Bootstrapper
{
  use ObserverTrait;

  const EVENT_PRE_BOOT = 0;

  const EVENT_BOOT = 1;

  const EVENT_POST_BOOT = 2;

  /**
   * @var InjectorInterface
   */
  private $injector;

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * Emits an event to all handlers registered to that event (if any), injecting the arguments to each calling handler.
   *
   * @param string $event The event name.
   */
  function emitAndInject ($event)
  {
    foreach (get ($this->observers, $event, []) as $l)
      $this->injector->execute ($l);
  }


}
