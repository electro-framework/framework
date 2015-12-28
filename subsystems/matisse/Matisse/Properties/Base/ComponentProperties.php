<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\TypeSystem\Reflection;
use Selenia\Matisse\Properties\TypeSystem\ReflectionClass;
use Selenia\Matisse\Properties\TypeSystem\type;

class ComponentProperties
{
  /**
   * A list of names of the properties that can be set while still considering the component's properties as not begin
   * modified.
   * @var string[]
   */
  static protected $NEVER_DIRTY = [];

  /**
   * Set to `true` when one or more properties have been changed from their default values, **at initialization time**.
   * @var bool
   */
  public $_modified = false;

  /**
   * The component that owns these properties.
   * @var Component
   */
  protected $component;
  /**
   * @var ReflectionClass
   */
  protected $metadata;

  function __construct (Component $ownerComponent, ReflectionClass $metadata)
  {
    $this->component = $ownerComponent;
    $this->metadata  = $metadata;
  }

  static function make ($ownerComponent)
  {
    $refClass = Reflection::instance ()->of (get_called_class ());
    return $refClass->newInstance ($ownerComponent, $refClass);
  }

  public function __get ($name)
  {
    throw new ComponentException ($this->component, "Can't read non existing property <b>$name</b>.");
  }

  public function __set ($name, $value)
  {
    throw new ComponentException ($this->component, "Can't set non existing property <b>$name</b>.");
  }

  public function apply (array $attrs)
  {
    foreach ($attrs as $k => $v)
      $this->set ($k, $v);
  }

  /**
   * Checks if the component supports the given attribute.
   *
   * @param string $name
   * @param bool   $asSubtag When true, the attribute MUST be able to be specified in subtag form.
   *                         When false, the attribute can be either a tag attribute or a subtag.
   * @return bool
   */
  public function defines ($name, $asSubtag = false)
  {
    if ($asSubtag) return $this->isSubtag ($name);
    return $this->metadata->hasProperty ($name);
  }

  public function get ($name, $default = null)
  {
    return property ($this, $name, $default);
  }

  public function getAll ()
  {
    $p = $this->getPropertyNames ();
    $r = [];
    foreach ($p as $prop)
      $r[$prop] = $this->{$prop};
    return $r;
  }

  /**
   * @param string $name Property name.
   * @return array Always returns an array, even if no enumeration is defined for the target property.
   * @throws \Selenia\Matisse\Exceptions\ReflectionPropertyException
   */
  public function getEnumOf ($name)
  {
    return $this->metadata->getProperty ($name)->enum ?: [];
  }

  public function getPropertiesOf ($type)
  {
    $result = [];
    $names  = $this->getPropertyNames ();
    if (isset($names))
      foreach ($names as $name)
        if ($this->getTypeOf ($name) == $type)
          $result[$name] = $this->get ($name);
    return $result;
  }

  public function getPropertyNames ()
  {
    return array_keys ($this->metadata->getProperties ());
  }

  public function getScalar ($name)
  {
    return $this->validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  public function getTypeNameOf ($name)
  {
    $id = type::getIdOf ($name);
    return type::getNameOf ($id);
  }

  public function getTypeOf ($name)
  {
    return $this->metadata->getProperty ($name)->type;
  }

  public function isEnum ($name)
  {
    return isset($this->metadata->getProperty ($name)->enum);
  }

  public function isScalar ($name)
  {
    $type = $this->getTypeOf ($name);
    return $type == type::bool || $type == type::id || $type == type::number ||
           $type == type::string;
  }

  public function isSubtag ($name)
  {
    if ($this->defines($name)) {
      $type = $this->getTypeOf ($name);
      switch ($type) {
        case type::content:
        case type::collection:
        case type::metadata:
          return true;
      }
    }
    return false;
  }

  public function set ($name, $value)
  {
    if (!$this->defines ($name))
      throw new ComponentException($this->component,
        sprintf ("Invalid property <kbd>%s</kbd> specified for a <kbd>%s</kbd> instance.", $name, shortTypeOf ($this)));
    if ($this->isScalar ($name))
      $this->setScalar ($name, $value);
    else switch ($type = $this->getTypeOf ($name)) {
      case type::content:
        $ctx  = $this->component->context;
        $text = Text::from ($ctx, $value);
        if (isset($this->$name))
          $this->$name->addChild ($text);
        else {
          $param = new Metadata ($ctx, $name, $type);
          $param->attachTo ($this->component);
          $param->addChild ($text);
          $this->$name = $param;
        }
        $this->_modified = true;
        break;
      default:
        $this->$name     = $value;
        $this->_modified = true;
    }
  }

  /**
   * Assign a new owner to the component. This will also do a deep clone of the component's properties.
   * @param Component $owner
   */
  public function setComponent (Component $owner)
  {
    $this->component = $owner;
    $props           = $this->getPropertiesOf (type::content);
    foreach ($props as $name => $value)
      if (!is_null ($value)) {
        /** @var Component $c */
        $c = clone $value;
        $c->attachTo ($owner);
        $this->$name = $c;
      }
    $props = $this->getPropertiesOf (type::collection);
    foreach ($props as $name => $values)
      if (!empty($values))
        $this->$name = Component::cloneComponents ($values, $owner);
  }

  public function setScalar ($name, $v)
  {
    if ($this->isEnum ($name)) {
      $enum = $this->getEnumOf ($name);
      if (array_search ($v, $enum) === false) {
        $list = implode ('</b>, <b>', $enum);
        throw new ComponentException ($this->component,
          "Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
      }
    }
    $newV = $this->validateScalar ($this->getTypeOf ($name), $v);
    if ($this->$name !== $newV) {
      $this->$name = $newV;
      if (!isset(static::$NEVER_DIRTY[$name]))
        $this->_modified = true;
    }
  }

  public function validateScalar ($type, $v)
  {
    if (!type::validate ($type, $v))
      throw new ComponentException ($this->component,
        sprintf (
          "%s is not a valid value for a component property of type <b>%s</b>",
          is_scalar ($v)
            ? sprintf ("<kbd>%s</kbd>", var_export ($v, true))
            : sprintf ("A value of PHP type <b>%s</b>", typeOf ($v)),
          type::getNameOf ($type)
        ));

    return type::typecast ($type, $v);
  }


}
