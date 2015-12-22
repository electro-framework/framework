<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\TypeSystem\is;
use Selenia\Matisse\Properties\TypeSystem\ReflectionProperty;
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
   * The type metadata for each class.
   * <p>Map of PHP class name => PropertiesMetadata
   * @var ReflectionProperty[]
   */
  static protected $_metadata = [];
  /**
   * Contains pre-initialized instances that will be cloned when new instances of a properties class are requested.
   * <p>A map of class name => ComponentProperties or one of its subclasses.
   * @var ComponentProperties[]
   */
  static protected $preInitialized = [];

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
   * @var ReflectionProperty
   */
  protected $metadata;

  protected function __construct ()
  {
    $this->initMetadata ();
    foreach ($this->metadata->defaults as $prop => $val)
      $this->$prop = $val;
  }

  static function make ($ownerComponent)
  {
    $class = get_called_class ();
    if (!isset(self::$preInitialized[$class]))
      static::$preInitialized[$class] = new static();
    $i            = clone static::$preInitialized[$class];
    $i->component = $ownerComponent;
    return $i;
  }

  public function validateScalar ($type, $v)
  {
    if (!type::validate ($type, $v))
      throw new ComponentException($this->component,
        sprintf (
          "%s is not a valid value for a component property of type <b>%s</b>",
          is_scalar ($v)
            ? sprintf ("<kbd>%s</kbd>", var_export ($v, true))
            : sprintf ("A value of PHP type <b>%s</b>", typeOf ($v)),
          type::getNameOf ($type)
        ));

    return type::typecast ($type, $v);
  }

  public function __get ($name)
  {
    throw new ComponentException($this->component, "Can't read non existing property <b>$name</b>.");
  }

  public function __set ($name, $value)
  {
    throw new ComponentException($this->component, "Can't set non existing property <b>$name</b>.");
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
    return isset ($this->metadata->types[$name]);
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

  public function getEnumOf ($name)
  {
    return get ($this->metadata->enums, $name, false);
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
    return array_keys ($this->metadata->types);
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
    return get ($this->metadata->types, $name);
  }

  public function isEnum ($name)
  {
    return isset($this->metadata->enums[$name]);
  }

  public function isScalar ($name)
  {
    $type = $this->getTypeOf ($name);
    return $type == type::bool || $type == type::id || $type == type::number ||
           $type == type::string;
  }

  public function isSubtag ($name)
  {
    $type = $this->getTypeOf ($name);
    switch ($type) {
      case type::content:
      case type::collection:
      case type::metadata:
        return true;
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
        throw new ComponentException($this->component,
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

  private function initMetadata ()
  {
    $className = get_class ($this);
    $refClass  = new \ReflectionClass($className);
    $meta      = $this->metadata = self::$_metadata[$className] = new ReflectionProperty;
    foreach ($refClass->getProperties (\ReflectionProperty::IS_PUBLIC) as $property) {
      $name  = $property->name;
      $value = $this->$name;
      if (!is_array ($value))
        $value = [$value];
      $it = new \ArrayIterator($value);
      while ($it->valid ()) {
        $v = $it->current ();
        if (is_string ($v)) {
          if ($v == '' || $v[0] != '~') // It's not metadata.
          {
            $meta->types[$name]    = type::string;
            $meta->defaults[$name] = $v;
          }
          else switch ($v) {
            case is::enum:
              $it->next ();
              if ($it->valid ()) {
                $e = $it->current ();
                if (is_array ($e))
                  $meta->enums[$name] = $e;
                else throw new ComponentException($this, "Invalid enumeration for the <kbd>$name</kbd> attribute");
              }
              else throw new ComponentException($this,
                "Missing argument for the <kbd>$name</kbd> attribute's enumeration");
              break;

            case type::string:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = '';
              break;

            case type::id:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = '';
              break;

            case type::number:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = 0;
              break;

            case type::bool:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = false;
              break;

            case type::content:
              $meta->types[$name]    = $v;
              $meta->defaults[$name] = null;
              break;

            case type::data:
              $meta->types[$name]    = $v;
              $meta->defaults[$name] = null;
              break;

            case type::metadata:
              $meta->types[$name]    = $v;
              $meta->defaults[$name] = null;
              break;

            case type::collection:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = [];
              break;

            case type::binding:
              $meta->types[$name] = $v;
              if (!array_key_exists ($name, $meta->defaults))
                $meta->defaults[$name] = '';
              break;

            case is::required:
              $meta->required[$name] = true;
              break;

            default:
              throw new ComponentException($this->component, "Invalid type declaration for the <kbd>$name</kbd> attribute"
              . sprintf("<p>Value: <kbd>%s</kbd>", var_export($v, true)) );
          }
        }
        else {
          // Set the default value explicitly.
          $meta->defaults[$name] = $v;

          // If the type has not yet been defined, it will be defined implicitly via the default value.
          if (!isset($meta->types[$name]))
            switch (gettype ($v)) {
              case 'string':
                $meta->types[$name] = type::string;
                break;
              case 'boolean':
                $meta->types[$name] = type::bool;
                break;
              case 'integer':
              case 'double':
                $meta->types[$name] = type::number;
                break;
              case 'NULL':
                // Don't bother setting a NULL default.
                break;
            }
        }
        $it->next ();
      }
      if (!isset($meta->types[$name]))
        throw new ComponentException($this->component, sprintf (
          "%s is missing a type declaration for the <kbd>%s</kbd> property",
          typeInfoOf ($this), $name));
    }
  }

}
