<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\IAttributes;

class ApplyAttributes extends ComponentAttributes
{
  public $content;
  public $attrs;

  protected function typeof_content () { return AttributeType::SRC; }

  protected function typeof_attrs () { return AttributeType::SRC; }
}

/**
 * A component that applies a set of attributes to all of its direct children.
 *
 * ##### Syntax:
 * ```
 * <c:apply>
 *   <p:attrs attr1="value1" ... attrN="valueN"/>
 *   content
 * </c:apply>
 *  ```
 * <p>The component's content should be composed of components that will receive the specified attributes.
 *
 */
class Apply extends Component implements IAttributes
{

  public $defaultAttribute = 'content';

  /**
   * Returns the component's attributes.
   * @return ApplyAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ApplyAttributes
   */
  public function newAttributes ()
  {
    return new ApplyAttributes($this);
  }

  protected function render ()
  {
    /** @var Parameter $attrParam */
    /** @var Parameter $content */
    /** @var Component $child */
    $attrParam = $this->attrs ()->attrs;
    $attrs     = $attrParam->attrs ()->getAll ();
    $content   = $this->getChildren ('content');
    foreach ($content as $k => $child)
      $child->attrs ()->apply ($attrs);
    $this->renderSet ($content);
  }

}
