<?php
namespace Selene\Matisse\Attributes;

class ParameterAttributes extends ComponentAttributes
{
  public function __get ($name)
  {
    if (property_exists ($this, $name)) return $this->$name;
    return null;
  }

  public function __set ($name, $value)
  {
    $this->$name = $value;
  }

  public function __isset ($name)
  {
    return property_exists ($this, $name);
  }

  public function defines ($name)
  {
    return true;
  }

}
