<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\TypeSystem\Reflection;
use Selenia\Matisse\Properties\TypeSystem\ReflectionClass;
use Selenia\Matisse\Properties\TypeSystem\type;

class ComponentProperties extends AbstractProperties
{
  /**
   * A list of names of the properties that can be set while still considering the component's properties as not begin
   * modified.
   *
   * @var string[]
   */
  static protected $NEVER_DIRTY = [];

  /**
   * Set to `true` when one or more properties have been changed from their default values, **at initialization time**.
   *
   * @var bool
   */
  public $_modified = false;

  /**
   * @var ReflectionClass
   */
  protected $metadata;

  function __construct (Component $ownerComponent)
  {
    parent::__construct ($ownerComponent);
    $this->metadata = Reflection::instance ()->of ($this);
    $this->metadata->init ($this);
  }

  function __get ($name)
  {
    throw new ComponentException ($this->component, "Can't read non existing property <b>$name</b>.");
  }

  function __set ($name, $value)
  {
    throw new ComponentException ($this->component, "Can't set non existing property <b>$name</b>.");
  }

  function defines ($name, $asSubtag = false)
  {
    if ($asSubtag) return $this->isSubtag ($name);
    return $this->metadata->hasProperty ($name);
  }

  function getEnumOf ($propName)
  {
    return $this->metadata->getProperty ($propName)->enum ?: [];
  }

  function getPropertyNames ()
  {
    return array_keys ($this->metadata->getProperties ());
  }

  function getTypeOf ($propName)
  {
    return $this->metadata->getProperty ($propName)->type;
  }

  function isEnum ($propName)
  {
    return isset($this->metadata->getProperty ($propName)->enum);
  }

  function set ($propName, $value)
  {
    if (!$this->defines ($propName))
      throw new ComponentException(
        $this->component,
        sprintf ("Invalid property <kbd>%s</kbd> specified for a %s instance.", $propName, typeInfoOf ($this))
      );
    if ($this->isScalar ($propName))
      $this->setScalar ($propName, $value);
    else switch ($type = $this->getTypeOf ($propName)) {
      case type::content:
        $ctx  = $this->component->context;
        $text = Text::from ($ctx, $value);
        if (isset($this->$propName))
          $this->$propName->addChild ($text);
        else {
          $param = new Metadata ($ctx, $propName, $type);
          $param->attachTo ($this->component);
          $param->addChild ($text);
          $this->$propName = $param;
        }
        $this->_modified = true;
        break;
      default:
        $this->$propName = $value;
        $this->_modified = true;
    }
  }

  function validateScalar ($type, $v)
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

  private function setScalar ($name, $v)
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


}
