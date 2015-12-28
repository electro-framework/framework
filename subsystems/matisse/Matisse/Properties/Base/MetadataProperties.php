<?php
namespace Selenia\Matisse\Properties\Base;

/**
 * Properties of a Content metadata component.
 */
class MetadataProperties extends ComponentProperties
{
  function __get ($name)
  {
    if (property_exists ($this, $name)) return $this->$name;
    return null;
  }

  function __set ($name, $value)
  {
    $this->$name = $value;
  }

  function __isset ($name)
  {
    return isset ($this->$name);
  }

  function defines ($name, $asSubtag = false)
  {
    return true;
  }

  function isScalar ($name)
  {
    return isset($this->name) ? is_scalar ($this->name) : true;
  }

  function setScalar ($name, $v)
  {
    $this->$name = $v;
  }
}
