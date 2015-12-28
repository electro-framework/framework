<?php
namespace Selenia\Matisse\Properties\TypeSystem;

use Selenia\Matisse\Traits\SingletonTrait;

class Reflection
{
  use SingletonTrait;

  /**
   * @var ReflectionClass[] A map of class name => reflection info.
   */
  private $classes = [];

  private function __construct () { }

  function of ($className)
  {
    $v = get ($this->classes, $className);
    return $v ?: ($this->classes[$className] = new ReflectionClass($className));
  }
}
