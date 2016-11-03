<?php
namespace Electro\DependencyInjection;

use Auryn\InjectionException;
use Auryn\Injector as Auryn;
use Electro\Interfaces\DI\InjectorInterface;

class Injector extends Auryn implements InjectorInterface, \ArrayAccess
{
  private $map = [];

  /**
   * {@inheritdoc}
   *
   * <p>This method provides container-interop compatibility.
   * ><p>**Note:** this is similar to {@see make()}, but it only retrieves symbolic names, not class or interface names.
   */
  public function get ($id)
  {
    if (!isset($this->map[$id]))
      throw new NotFoundException ("The <kbd>$id</kbd> symbolic name is not registered");
    try {
      return $this->make ($id);
    }
    catch (InjectionException $e) {
      throw new NotFoundException ($e->getMessage ());
    }
  }

  public function getMapping ($symbolicName)
  {
    return isset($this->map[$symbolicName]) ? $this->map[$symbolicName] : null;
  }

  /**
   * {@inheritdoc}
   *
   * <p>This method provides container-interop compatibility.
   * ><p>**Note:** this is similar to {@see provides()}, but it only checks symbolic names, not class or interface
   * names.
   */
  public function has ($id)
  {
    return isset($this->map[$id]);
  }

  public function make ($name, array $args = [])
  {
    if (isset($this->map[$name]))
      $name = $this->map[$name];
    return parent::make ($name, $args);
  }

  public function makeFactory ($name, array $args = [])
  {
    return function () use ($name, $args) {
      return $this->make ($name, $args);
    };
  }

  public function offsetExists ($offset)
  {
    return isset($this->map[$offset]);
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

  /**
   * {@inheritdoc}
   *
   * ><p>**Note:** due to technical limitations of Auryn, this operation is slow. Avoid using it.
   */
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
