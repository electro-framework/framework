<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\IAttributes;

class IfAttributes extends ComponentAttributes
{
  public $the;
  public $is;
  public $is_set  = false;
  public $is_true = false;
  public $not     = false;
  public $matches;
  public $case;          //note: doesn't work with databinding
  public $then;
  public $else;

  protected function typeof_the () { return AttributeType::TEXT; }

  protected function typeof_is () { return AttributeType::TEXT; }

  protected function typeof_is_set () { return AttributeType::BOOL; }

  protected function typeof_is_true () { return AttributeType::BOOL; }

  protected function typeof_not () { return AttributeType::BOOL; }

  protected function typeof_matches () { return AttributeType::TEXT; }

  protected function typeof_case () { return AttributeType::PARAMS; }

  protected function typeof_then () { return AttributeType::SRC; }

  protected function typeof_else () { return AttributeType::SRC; }
}

/**
 * Rendes content blocks conditionally.
 *
 * ##### Syntax:
 * ```
 * <c:if the="value1" is="value2">
 *   content if true
 *   <p:else> content if false </p:else>
 * </c:if>
 *
 * <c:if is="value"> content if value is truthy </c:if>
 *
 * <c:if not is="value"> content if value is falsy </c:if>
 *
 * <c:if the="value" is-true> content if value is truthy </c:if>
 *
 * <c:if the="value" not is-true> content if value is falsy </c:if>
 *
 * <c:if the="value" is-set> content if value is different from null and the empty string </c:if>
 *
 * <c:if the="value" not is-set> content if value is equal to null or an empty string </c:if>
 *
 * <c:if the="value" matches="regexp"> content if value matches the regular expression </c:if>
 *
 * <c:if the="value" not matches="regexp"> content if value doesn't matche the regular expression </c:if>
 *
 * <c:if the="value">
 *   <p:case is="value1"> content if value == value1 </p:case>
 *   ...
 *   <p:case is="valueN"> content if value == valueN </p:case>
 *   <p:else> content if no match </p:else>
 * </c:if>
 * ```
 */
class If_ extends Component implements IAttributes
{

  public $defaultAttribute = 'then';

  /**
   * Returns the component's attributes.
   * @return IfAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return IfAttributes
   */
  public function newAttributes ()
  {
    return new IfAttributes($this);
  }

  protected function render ()
  {
    $this->switchContent ();
    $this->renderChildren ();
  }

  private function switchContent ()
  {
    // Clear the current children (required if the component is being repeated)
    $this->setChildren ([]);
    $attr = $this->attrs ();

    $v   = $attr->get ('the');
    $is  = $attr->get ('is');
    $not = $attr->not;

    if (isset($is)) {
      if (!isset($v)) {
        $is = isset($is) && $is != '';
        $v  = true;
      }
      if ($v == $is xor $not)
        $this->setChildren ($this->getChildren ('then'));
      else $this->setChildren ($this->getChildren ('else'));
      return;
    }

    if (isset($attr->is_set)) {
      if ((isset($v) && $v != '') xor $not)
        $this->setChildren ($this->getChildren ('then'));
      else $this->setChildren ($this->getChildren ('else'));
      return;
    }

    if (isset($attr->is_true)) {
      if ($v xor $not)
        $this->setChildren ($this->getChildren ('then'));
      else $this->setChildren ($this->getChildren ('else'));
      return;
    }

    if (isset($attr->matches)) {
      if (preg_match ("%$attr->matches%", $v) xor $not)
        $this->setChildren ($this->getChildren ('then'));
      else $this->setChildren ($this->getChildren ('else'));
      return;
    }

    if (isset($attr->case)) {
      foreach ($attr->case as $param) {
//        $param->databind ();
        if ($v == $param->attrs ()->is) {
//          $children = self::cloneComponents ($param->children);
//          $this->setChildren ($children);
          $this->setChildren ($param->children);
          return;
        }
      }
      $this->setChildren ($this->getChildren ('else'));
      return;
    }

    if ($v)
      $this->setChildren ($this->getChildren ('then'));
    else $this->setChildren ($this->getChildren ('else'));
  }

}
