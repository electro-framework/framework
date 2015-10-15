<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\IAttributes;

class ApplyAttributes extends ComponentAttributes
{
  public $attrs;
  public $where;
  public $recursive;

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
    if (!$where && !empty($this->children)) {
      foreach ($this->children as $k => $child)
        $child->attrs ()->apply ($attrs);
    }
    else $this->scan ($this, $attr->where, $attrs);
    $this->renderChildren();
  }

  private function scan (Component $parent, $where, $attrs)
  {
    if (isset($parent->children))
      foreach ($parent->children as $child) {
        if ($child->getTagName () == $where)
          $child->attrs ()->apply ($attrs);
        $this->scan ($child, $where, $attrs);
      }
    return;
    /** @var ComponentAttributes $params */
    /*
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
    }*/

  }

}
