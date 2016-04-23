<?php
namespace Selenia\Core\DependencyInjection;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceContainerInterface;
use Selenia\Interop\Map;

/**
 * The service container is a directory service that maps abstract service names (ex: 'app') to concrete
 * implementations.
 *
 * <p>When reading from the container, an injector is invoked to create or retrieve a concrete implementation instance
 * for the specified key. Whether repeatable reads of the same key return the same instance or a new instance depends
 * solely on the injector.
 *
 * <p>This class requires that an injector implementing {@see InjectorInterface} is provided on its constructor call.
 *
 * ><p>**Note:** most of the container's functionality is already provided by the injector; what his class does is
 * little more than extending the injector's lookup functionality to allow you to associate arbitrary keys with
 * injectable values, instead of being limited to class and/or interface names.
 */
class ServiceContainer extends Map implements ServiceContainerInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  public function __get ($key)
  {
    return $this->get ($key);
  }

  public function get ($alias)
  {
    return ($c = $this->getRaw ($alias)) ? $this->injector->make ($c) : null;
  }

  public function getRaw ($key)
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : null;
  }

  public function offsetGet ($offset)
  {
    return $this->get ($offset);
  }

}
