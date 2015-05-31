<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\Context;
use Selene\Matisse\GenericAttributes;
use Selene\Matisse\IAttributes;

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

  public $allowsChildren = true;

  public function __construct (Context $context, $tagName, $type, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->type = $type;
    $this->setTagName ($tagName);
  }

  /**
   * @see IAttributes::attrs()
   * @return GenericAttributes
   */
  function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return GenericAttributes
   */
  function newAttributes ()
  {
    return new GenericAttributes($this);
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
