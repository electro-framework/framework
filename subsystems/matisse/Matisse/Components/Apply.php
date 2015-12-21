<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class ApplyProperties extends ComponentProperties
{
  /**
   * @var ContentProperty|null
   */
  public $attrs = type::content;
  /**
   * @var bool
   */
  public $recursive = false;
  /**
   * @var string
   */
  public $where = '';
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
class Apply extends Component implements PropertiesInterface
{
  public $allowsChildren = true;

  /**
   * Returns the component's properties.
   * @return ApplyProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Creates an instance of the component's properties.
   * @return ApplyProperties
   */
  public function newProperties ()
  {
    return new ApplyProperties($this);
  }

  protected function render ()
  {
    $attr = $this->props ();
    /** @var ContentProperty $attrParam */
    /** @var ContentProperty $content */
    $attrParam = $attr->props;
    $attrs     = $attrParam->props ()->getAll ();
    $where     = $attr->where;
    if (!$where && $this->hasChildren ()) {
      foreach ($this->getChildren () as $k => $child)
        $child->props ()->apply ($attrs);
    }
    else $this->scan ($this, $attr->where, $attrs);
    $this->renderChildren ();
  }

  private function scan (Component $parent, $where, $attrs)
  {
    if ($parent->hasChildren ())
      foreach ($parent->getChildren () as $child) {
        if ($child->getTagName () == $where)
          $child->props ()->apply ($attrs);
        $this->scan ($child, $where, $attrs);
      }
    return;
    /** @var ComponentProperties $params */
    /*
    $params = $parent->props ();
    foreach ($params->getAll () as $param) {
      if ($param instanceof Parameter) {
        $content = $param->children;
        if (isset($content))
          foreach ($content as $k => $child) {
            if ($child->getTagName () == $where)
              $child->props ()->apply ($attrs);
            $this->scan ($child, $where, $attrs);
          }
      }
    }*/

  }

}
