<?php
namespace Selenia\Core\DependencyInjection;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceContainerInterface;

class ServiceContainer extends \Selenia\Interop\Map implements ServiceContainerInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  public function get ($alias)
  {
    return ($serviceClass = $this[$alias]) ? $this->injector->make ($serviceClass) : null;
  }

}
