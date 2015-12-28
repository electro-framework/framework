<?php
namespace Selenia\Matisse\Properties\TypeSystem;

class ReflectionClass
{
  /**
   * @var string The full class name.
   */
  private $name;
  /**
   * @var ReflectionProperty[] A map of property names => reflection info.
   */
  private $props = [];

  /**
   * @param string $className
   */
  function __construct ($className)
  {
    $this->name = $className;
  }

  function getName ()
  {
    return $this->name;
  }

  function properties ()
  {
    return $this->props;
  }

  function property ($name)
  {
    $v = get ($this->props, $name);
    return $v ?: ($this->props[$name] = new ReflectionProperty($name));
  }

}
