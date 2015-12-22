<?php
namespace Selenia\Matisse\Properties\Base;

/**
 * Properties of a Content metadata component.
 */
class MetadataProperties extends ComponentProperties
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

  public function defines ($name, $asSubtag = false)
  {
    return true;
  }

  public function __isset ($name)
  {
    return isset ($this->$name);
  }

}
