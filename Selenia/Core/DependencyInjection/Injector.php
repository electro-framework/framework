<?php
namespace Selenia\Core\DependencyInjection;

use Auryn\Injector as Auryn;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceContainerInterface;

class Injector extends Auryn implements InjectorInterface
{
  /**
   * Note: this is lazily constructed.
   *
   * @var ServiceContainerInterface
   */
  private $container = null;

  function getContainer ()
  {
    return $this->container ?: $this->container = $this->make (ServiceContainerInterface::class);
  }

  public function makeFactory ($name, array $args = [])
  {
    return function () use ($name, $args) {
      return $this->make ($name, $args);
    };
  }

  function provides ($name)
  {
    $r = $this->inspect (strtolower ($name), Injector::I_ALIASES | Injector::I_DELEGATES | Injector::I_SHARES);
    return !empty(array_filter ($r));
  }

  function register ($typeName, $symbolicName)
  {
    $this->getContainer ()[$symbolicName] = $typeName;
    return $this;
  }

  function share ($nameOrInstance, $symbolicName = null)
  {
    $i = parent::share ($nameOrInstance);
    if ($symbolicName)
      $this->getContainer ()[$symbolicName] = $nameOrInstance;
    return $i;
  }

}
