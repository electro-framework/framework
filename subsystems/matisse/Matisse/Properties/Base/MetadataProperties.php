<?php
namespace Selenia\Matisse\Properties\Base;

use JsonSerializable;
use Selenia\Matisse\Properties\TypeSystem\type;
use Selenia\Traits\InspectionTrait;

/**
 * Properties of a Metadata component.
 */
class MetadataProperties extends AbstractProperties implements JsonSerializable
{
  use InspectionTrait;

  private static $INSPECTABLE = ['props'];

  /**
   * Dynamic set of attributes, as specified on the source markup.
   *
   * @var array
   */
  protected $props = [];

  function __get ($name)
  {
    return isset($this->props[$name]) ? $this->props[$name] : null;
  }

  function __set ($name, $value)
  {
    $this->props[$name] = $value;
  }

  function __isset ($name)
  {
    return isset ($this->props[$name]);
  }

  function __unset ($name)
  {
    unset ($this->props[$name]);
  }

  function defines ($name, $asSubtag = false)
  {
    return true;
  }

  function get ($propName, $default = null)
  {
    return get ($this->props, $propName, $default);
  }

  function getAll ()
  {
    return array_merge (object_publicProps ($this), $this->props);
  }

  /**
   * Gets a map of the dynamic (non-predefined) properties of the component.
   * <p>Properties declared on the class are excluded.
   *
   * @return array A map of property names to property values.
   */
  function getDynamic ()
  {
    return $this->props;
  }

  function getEnumOf ($propName)
  {
    return [];
  }

  function getPropertyNames ()
  {
    return array_merge (object_propNames ($this), array_keys ($this->props));
  }

  function getRelatedTypeOf ($propName)
  {
    return type::content;
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

  /**
   * **Note:** this is useful for the `json` filter, for instance.
   *
   * @return array
   */
  function jsonSerialize ()
  {
    return $this->getAll ();
  }

  function set ($propName, $value)
  {
    $this->$propName = $value;
  }

}
