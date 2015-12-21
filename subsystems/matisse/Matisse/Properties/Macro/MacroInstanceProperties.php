<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class MacroInstanceProps
{
  /**
   * @var ContentProperty[]
   */
  public $script = type::collection;
  /**
   * @var ContentProperty[]
   */
  public $style = type::collection;

  /**
   * Points to the component that defines the macro for these attributes.
   * @var Macro
   */
  protected $macro;
  /**
   * Dynamic set of attributes, as specified on the source markup.
   * @var array
   */
  private $props;

  public function __construct (Component $component, Macro $macro)
  {
    $this->macro = $macro;
  }

  public function __get ($name)
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

  public function __set ($name, $value)
  {
    if (!isset($this->props))
      $this->props = [$name => $value];
    else
      $this->props[$name] = $value;
  }

  public function __isset ($name)
  {
    return isset($this->props) && array_key_exists ($name, $this->props);
  }

  public function defines ($name)
  {
    return $this->isPredefined ($name) || !is_null ($this->macro->getParameter ($name));
  }

  public function get ($name, $default = null)
  {
    $v = $this->__get ($name);
    if (is_null ($v))
      return $default;

    return $v;
  }

  public function getAll ()
  {
    return $this->props;
  }

  public function getAttributeNames ()
  {
    return $this->macro->getParametersNames ();
  }

  public function getDefault ($name)
  {
    $param = $this->macro->getParameter ($name);
    if (is_null ($param))
      throw new ComponentException($this->macro, "Undefined parameter $name.");

    return $this->macro->getParameter ($name)->props ()->default;
  }

  public function getScalar ($name)
  {
    return ComponentProperties::validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  public function getTypeNameOf ($name)
  {
    $t = $this->getTypeOf ($name);
    if (!is_null ($t))
      return ComponentProperties::$TYPE_NAMES[$t];

    return null;
  }

  public function getTypeOf ($name)
  {
    if ($this->isPredefined ($name)) {
      $fn = "typeof_$name";
      if (method_exists ($this, $fn))
        return $this->$fn();

      return null;
    }

    return $this->macro->getParameterType ($name);
  }

  public function isPredefined ($name)
  {
    return method_exists ($this, "typeof_$name");
  }

  public function set ($name, $value)
  {
    $this->$name = $value;
  }

  public function setScalar ($name, $v)
  {
    /*
      if ($this->isEnum($name)) {
      $enum = $this->getEnumOf($name);
      if (array_search($v,$enum) === FALSE) {
      $list = implode('</b>, <b>',$enum);
      throw new ComponentException($this->component,"Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
      }
      } */
    $this->props[$name] = ComponentProperties::validateScalar ($this->getTypeOf ($name), $v, $name);
  }

}
