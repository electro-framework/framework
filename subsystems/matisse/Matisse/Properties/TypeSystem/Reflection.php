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

}
