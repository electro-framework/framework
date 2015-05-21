<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\Context;
use Selene\Matisse\IAttributes;

class ParameterAttributes extends ComponentAttributes
{
  /**
   * Dynamic set of attributes, as specified on the source markup.
   * @var array
   */
  private $attributes;

  public function __get ($name)
  {
    if (isset($this->attributes)) {
      if (array_key_exists ($name, $this->attributes)) return $this->attributes[$name];
      return null;
    }
    return null;
  }

  public function __set ($name, $value)
  {
    if (!isset($this->attributes)) $this->attributes = [$name => $value];
    else $this->attributes[$name] = $value;
  }

  public function __isset ($name)
  {
    return isset($this->attributes) && array_key_exists ($name, $this->attributes);
  }

  public function getTypeOf ($name)
  {
    return AttributeType::TEXT;
  }

  public function defines ($name)
  {
    return true;
  }

  public function getAttributeNames ()
  {
    return isset($this->attributes) ? array_keys ($this->attributes) : null;
  }

  public function getAll ()
  {
    return $this->attributes;
  }
}

class Parameter extends Component implements IAttributes
{

  /**
   * The ComponentAttributes type of the parameter's value.
   * @var number
   */
  public $type;

  /**
   * The parameter's scalar value.
   * Note that data sources are also considered scalar values.
   * @var mixed
   */
  public $value;

  /**
   * This is used by the Parser for it to know when an implicit parameter is being used.
   * It is set to `true` when this parameter is not present on the markup and it was created as an implicit parameter.
   * This usually happens when content is placed immediately after a component's opening tag and a default parameter
   * must be generated to hold that content.
   * @var Parameter
   */
  public $isImplicit;

  public function __construct (Context $context, $tagName, $type, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->type      = $type;
    $this->setTagName ($tagName);
  }

  /**
   * @see IAttributes::attrs()
   * @return ParameterAttributes
   */
  function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return ParameterAttributes
   */
  function newAttributes ()
  {
    return new ParameterAttributes($this);
  }

  public function setScalar ($v)
  {
    $this->value = ComponentAttributes::validateScalar ($this->type, $v);
  }

  public function isScalar ()
  {
    //Note that parameters are never of type TYPE_PARAMS.
    return $this->type != AttributeType::SRC;
  }

  public function getValue ()
  {
    if ($this->type == AttributeType::SRC)
      return $this->children;
    return $this->value;
  }

  public function parsed ()
  {
    $this->databind ();
  }

  protected function render ()
  {
    $this->parent->renderParameter ($this);
  }
}

