<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Attributes\ParameterAttributes;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Context;
use Selenia\Matisse\IAttributes;

/**
 * A complex attribute that is expressed as a subtag.
 *
 * ><p>**Note:** rendering a parameter **does not** render its children.
 * <p>Otherwise problems would occur when rendering a component's children, as some of those components may be parameters.
 * <p>The content of parameters **must always** be rendered manually on the owner component's `render()`.
 */
class Parameter extends Component implements IAttributes
{
  public $allowsChildren = true;
  /**
   * The AttributeType type of the parameter's value.
   * @var number
   */
  public $type;
  /**
   * The parameter's scalar value.
   * Note that data sources are also considered scalar values.
   * @var mixed
   */
  public $value;

  public function __construct (Context $context, $tagName, $type, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->type = $type;
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

  public function getValue ()
  {
    if ($this->type == AttributeType::SRC)
      return $this->getChildren ();
    return $this->value;
  }

  public function isScalar ()
  {
    //Note that parameters are never of type TYPE_PARAMS.
    return $this->type != AttributeType::SRC;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return ParameterAttributes
   */
  function newAttributes ()
  {
    return new ParameterAttributes($this);
  }

  public function parsed ()
  {
    $this->databind ();
  }

  public function setScalar ($v)
  {
    $this->value = ComponentAttributes::validateScalar ($this->type, $v);
  }

}
