<?php
namespace Selenia\Core\DependencyInjection;

use Auryn\InjectionException;
use Auryn\Injector as Auryn;
use Selenia\Interfaces\DI\InjectorInterface;

class Injector extends Auryn implements InjectorInterface, \ArrayAccess
{
  private $map = [];

  public function get ($id)
  {
    try {
      return $c = isset($this->map[$id]) ? $this->make ($this->map[$id]) : $this->make ($id);
    }
    catch (InjectionException $e) {
      throw new NotFoundException ($e->getMessage ());
    }
  }

  public function getMapping ($symbolicName)
  {
    return isset($this->map[$symbolicName]) ? $this->map[$symbolicName] : null;
  }

  public function has ($id)
  {
    return isset($this->map[$id]) || $this->provides ($id);
  }

  public function makeFactory ($name, array $args = [])
  {
    return function () use ($name, $args) {
      return $this->make ($name, $args);
    };
  }

  public function offsetExists ($offset)
  {
    return $this->has ($offset);
  }

  public function offsetGet ($offset)
  {
    return $this->get ($offset);
  }

  public function offsetSet ($offset, $value)
  {
    if (is_string ($value))
      $this->map[$offset] = $value;
    else $this->share ($value, $offset);
  }

  public function offsetUnset ($offset)
  {
    unset ($this->map[$offset]);
  }

  public function provides ($name)
  {
    $r = $this->inspect (strtolower ($name), Injector::I_ALIASES | Injector::I_DELEGATES | Injector::I_SHARES);
    return !empty(array_filter ($r));
  }

  public function set ($symbolicName, $nameOrInstance)
  {
    $this->offsetSet ($symbolicName, $nameOrInstance);
    return $this;
  }

  public function share ($nameOrInstance, $symbolicName = null)
  {
    $i = parent::share ($nameOrInstance);
    if ($symbolicName)
      $this->map[$symbolicName] = is_string ($nameOrInstance) ? $nameOrInstance : get_class ($nameOrInstance);
    return $i;
  }

}
