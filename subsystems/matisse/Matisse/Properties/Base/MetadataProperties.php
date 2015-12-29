<?php
namespace Selenia\Matisse\Properties\Base;

/**
 * Properties of a Metadata component.
 */
class MetadataProperties extends AbstractProperties
{
  function __get ($name)
  {
    return property_exists ($this, $name) ? $this->$name : null;
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

  function getEnumOf ($propName)
  {
    return [];
  }

  function getPropertyNames ()
  {
    return array_keys (getPublicProperties ($this));
  }

  function getTypeOf ($propName)
  {
    return null;
  }

  function isEnum ($propName)
  {
    return false;
  }

  function isScalar ($name)
  {
    return isset($this->name) ? is_scalar ($this->name) : true;
  }

  function set ($propName, $value)
  {
    $this->$propName = $value;
  }

}
