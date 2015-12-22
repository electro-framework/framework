<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class MacroInstanceProperties extends ComponentProperties
{
  /**
   * @var Metadata[]
   */
  public $script = type::collection;
  /**
   * @var Metadata[]
   */
  public $style = type::collection;

  /**
   * Points to the component that defines the macro for these attributes.
   * @var Macro
   */
  private $macro;
  /**
   * Dynamic set of attributes, as specified on the source markup.
   * @var array
   */
  private $props = [];

  function __get ($name)
  {
    if (isset($this->props)) {
      $v = get ($this->props, $name);
      if (!is_null ($v) && $v != '')
        return $v;
    }
    $macroParam = $this->macro->getParameter ($name);
    if (isset($macroParam->bindings) && array_key_exists ('default', $macroParam->bindings))
      return $macroParam->bindings['default'];

    return $this->getDefault ($name);
  }

  function __set ($name, $value)
  {
     $this->props[$name] = $value;
  }

  function __isset ($name)
  {
    return isset($this->props[$name]);
  }

  function defines ($name, $asSubtag = false)
  {
    return $this->isPredefined ($name) || !is_null ($this->macro->getParameter ($name));
  }

  function get ($name, $default = null)
  {
    $v = $this->__get ($name);
    if (is_null ($v))
      return $default;

    return $v;
  }

  function getAll ()
  {
    return $this->props;
  }

  function getDefault ($name)
  {
    $param = $this->macro->getParameter ($name);
    if (is_null ($param))
      throw new ComponentException($this->macro, "Undefined parameter $name.");

    return $this->macro->getParameter ($name)->props ()->default;
  }

  function getPropertyNames ()
  {
    return $this->macro->getParametersNames ();
  }

  function getScalar ($name)
  {
    return $this->validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  function getTypeNameOf ($name)
  {
    $t = $this->getTypeOf ($name);
    if (!is_null ($t))
      return ComponentProperties::$TYPE_NAMES[$t];

    return null;
  }

  function getTypeOf ($name)
  {
    if ($this->isPredefined ($name)) {
      $fn = "typeof_$name";
      if (method_exists ($this, $fn))
        return $this->$fn();

      return null;
    }

    return $this->macro->getParameterType ($name);
  }

  function isPredefined ($name)
  {
    return method_exists ($this, "typeof_$name");
  }

  function set ($name, $value)
  {
    $this->$name = $value;
  }

  function setMacro (Macro $macro)
  {
    $this->macro = $macro;
  }

  function setScalar ($name, $v)
  {
    /*
      if ($this->isEnum($name)) {
      $enum = $this->getEnumOf($name);
      if (array_search($v,$enum) === FALSE) {
      $list = implode('</b>, <b>',$enum);
      throw new ComponentException($this->component,"Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
      }
      } */
    $this->props[$name] = $this->validateScalar ($this->getTypeOf ($name), $v);
  }

}
