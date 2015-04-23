<?php
namespace selene\matisse\components;
use selene\matisse\AttributeType;
use selene\matisse\Component;
use selene\matisse\ComponentAttributes;
use selene\matisse\IAttributes;

class TestAttributes extends ComponentAttributes
{
  public $value;
  public $equals;
  public $is_set;
  public $is_not_set;
  public $if_set;
  public $if_not_set;
  public $if_true;
  public $if_false;
  public $if_equals;
  public $if_not_equals;
  public $if_match;
  public $case;          //note: doesn't work with databinding
  public $default;

  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_equals () { return AttributeType::TEXT; }
  protected function typeof_is_set () { return AttributeType::TEXT; }
  protected function typeof_is_not_set () { return AttributeType::TEXT; }
  protected function typeof_if_set () { return AttributeType::SRC; }
  protected function typeof_if_not_set () { return AttributeType::SRC; }
  protected function typeof_if_true () { return AttributeType::SRC; }
  protected function typeof_if_false () { return AttributeType::SRC; }
  protected function typeof_if_equals () { return AttributeType::SRC; }
  protected function typeof_if_not_equals () { return AttributeType::SRC; }
  protected function typeof_if_match () { return AttributeType::SRC; }
  protected function typeof_case () { return AttributeType::PARAMS; }
  protected function typeof_default () { return AttributeType::SRC; }
}

class Test extends Component implements IAttributes
{

  public $defaultAttribute = 'if_true';

  /**
   * Returns the component's attributes.
   * @return TestAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return TestAttributes
   */
  public function newAttributes ()
  {
    return new TestAttributes($this);
  }

  private function switchContent ()
  {
    $isSet = $this->attrs ()->get ('is_set');
    if (isset($isSet)) {
      $this->setChildren ($this->getChildren ('if_true'));
      return;
    }
    $isNotSet = $this->attrs ()->get ('is_not_set');
    if (isset($isNotSet)) {
      $this->setChildren ($this->getChildren ('if_true'));
      return;
    }
    $value  = $this->attrs ()->get ('value');
    $equals = $this->attrs ()->get ('equals');
    if (isset($equals))
      $value = $value == $equals;
    if (isset($this->attrs ()->if_equals)) {
      $param = $this->attrs ()->if_equals;
      $param->databind ();
      if ($value == $param->attrs ()->value) {
        $this->setChildren ($this->getChildren ('if_equals'));
        return;
      }
    }
    if (isset($this->attrs ()->if_match)) {
      $param = $this->attrs ()->if_match;
      $param->databind ();
      if (preg_match ('%' . $param->attrs ()->value . '%', $value)) {
        $this->setChildren ($this->getChildren ('if_match'));
        return;
      }
    }
    if (isset($this->attrs ()->if_not_equals)) {
      $param = $this->attrs ()->if_not_equals;
      $param->databind ();
      if ($value != $param->attrs ()->value) {
        $this->setChildren ($this->getChildren ('if_not_equals'));
        return;
      }
    }
    if (isset($this->attrs ()->case)) {
      foreach ($this->attrs ()->case as $param) {
        $param->databind ();
        if ($value == $param->attrs ()->value) {
          $children = self::cloneComponents ($param->children);
          $this->setChildren ($children);
          return;
        }
      }
    }
    if (isset($this->attrs ()->if_not_set) && (is_null ($value) || $value == '')) {
      $this->setChildren ($this->getChildren ('if_not_set'));
      return;
    }
    if (isset($this->attrs ()->if_set) && !is_null ($value) && $value != '') {
      $this->setChildren ($this->getChildren ('if_set'));
      return;
    }
    $b = ComponentAttributes::getBoolean ($value);
    if (isset($this->attrs ()->if_true) && $b === true) {
      $this->setChildren ($this->getChildren ('if_true'));
      return;
    }
    if (isset($this->attrs ()->if_false) && $b === false) {
      $this->setChildren ($this->getChildren ('if_false'));
      return;
    }
    if (isset($this->attrs ()->default)) {
      $this->setChildren ($this->getChildren ('default'));
      return;
    }
  }

  protected function render ()
  {
    $this->switchContent ();
    $this->renderChildren ();
  }

}
