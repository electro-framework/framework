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
  public $where;

  protected function typeof_content () { return AttributeType::SRC; }

  protected function typeof_attrs () { return AttributeType::SRC; }

  protected function typeof_where () { return AttributeType::TEXT; }

  protected function typeof_recursive () { return AttributeType::BOOL; }
}

/**
 * A component that applies a set of attributes to its children, optionally filtered by tag name.
 *
 * This is useful when the attributes have dynamic values, otherwise use 'presets', as they are less computationally
 * expensive.
 *
 * ##### Syntax:
 * ```
 * <Apply [where="tag-name"]>
 *   <Attrs attr1="value1" ... attrN="valueN"/>
 *   content
 * </Apply>
 *  ```
 * <p>If no filter is provided, only direct children of the component will be affected.
 *
 */
class Apply extends Component implements IAttributes
{
  public $allowsChildren = true;

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
    $attr = $this->attrs ();
    /** @var Parameter $attrParam */
    /** @var Parameter $content */
    $attrParam = $attr->attrs;
    $attrs     = $attrParam->attrs ()->getAll ();
    $where     = $attr->where;
    $content   = $this->getChildren ('content');
    if (!$where) {
      foreach ($content as $k => $child)
        $child->attrs ()->apply ($attrs);
    }
    else $this->scan ($this, $this->attrs ()->where, $attrs);
    $this->renderSet ($content);
  }

  private function scan (Component $parent, $where, $attrs)
  {
    /** @var ComponentAttributes $params */
    $params = $parent->attrs ();
    foreach ($params->getAll () as $param) {
      if ($param instanceof Parameter) {
        $content = $param->children;
        if (isset($content))
          foreach ($content as $k => $child) {
            if ($child->getTagName () == $where)
              $child->attrs ()->apply ($attrs);
            $this->scan ($child, $where, $attrs);
          }
      }
    }

  }

}
