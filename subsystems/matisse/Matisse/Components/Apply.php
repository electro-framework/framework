<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class ApplyProperties extends ComponentProperties
{
  /**
   * @var bool
   */
  public $recursive = false;
  /**
   * @var Metadata|null
   */
  public $set = type::metadata;
  /**
   * @var string
   */
  public $where = '';
}

/**
 * A component that applies a set of property values to the instance's children, optionally filtered by tag name.
 *
 * This is useful when the properties have dynamic values, otherwise use 'presets', as they are less computationally
 * expensive.
 *
 * ##### Syntax:
 * ```
 * <Apply [where="tag-name"]>
 *   <Set prop1="value1" ... propN="valueN"/>
 *   content
 * </Apply>
 *  ```
 * <p>If no filter is provided, only direct children of the component will be affected.
 * > **Note:** you can use data-bindings on the property values of `<Set>`
 */
class Apply extends Component
{
  const allowsChildren = true;
  
  const propertiesClass = ApplyProperties::class;
  
  /** @var ApplyProperties */
  public $props;

  protected function render ()
  {
    $setterProp = $this->props->set;
    $props      = $setterProp->props->getAll ();
    $where      = $this->props->where;
    if (!$where && $this->hasChildren ()) {
      foreach ($this->getChildren () as $k => $child)
        $child->props->apply ($props);
    }
    else $this->scan ($this, $this->props->where, $props);
    $this->runChildren ();
  }

  private function scan (Component $parent, $where, $attrs)
  {
    if ($parent->hasChildren ())
      foreach ($parent->getChildren () as $child) {
        if ($child->getTagName () == $where)
          $child->props->apply ($attrs);
        $this->scan ($child, $where, $attrs);
      }
    return;
    //TODO: enable recursive mode
    /** @var ComponentProperties $params */
    /*
    $params = $parent->props;
    foreach ($params->getAll () as $param) {
      if ($param instanceof Parameter) {
        $content = $param->children;
        if (isset($content))
          foreach ($content as $k => $child) {
            if ($child->getTagName () == $where)
              $child->props->apply ($attrs);
            $this->scan ($child, $where, $attrs);
          }
      }
    }*/

  }

}
