<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Base\ContentProperties;
use Selenia\Matisse\Properties\Types\type;

/**
 * A complex property that is expressed as a subtag.
 *
 * > <p>**Note:** rendering a parameter **does not** render its children.
 * > <p>Otherwise problems would occur when rendering a component's children, as some of those components may be
 * parameters.
 * > <p>The content of parameters **must always** be rendered manually on the owner component's `render()`.
 */
class ContentProperty extends Component implements PropertiesInterface
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
   * @return ContentProperties
   */
  function props ()
  {
    return $this->props;
  }

  public function getValue ()
  {
    if ($this->type == type::content)
      return $this->getChildren ();
    return $this->value;
  }

  public function isScalar ()
  {
    //Note that parameters are never of type TYPE_PARAMS.
    return $this->type != type::content;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return ContentProperties
   */
  function newProperties ()
  {
    return new ContentProperties($this);
  }

  public function parsed ()
  {
    $this->databind ();
  }

  public function setScalar ($v)
  {
    $this->value = ComponentProperties::validateScalar ($this->type, $v);
  }

}
