<?php
use selene\matisse\AttributeType;
use selene\matisse\ComponentAttributes;
use selene\matisse\VisualComponent;

class InputAttributes extends ComponentAttributes {
  public $name;
  public $value;
  public $type;
  public $autofocus = false;
  public $read_only = false;
  public $autoselect = false;
  public $autocomplete = true;
  public $on_change;
  public $action = '';
  public $date_format = 'Y-m-d';
  public $max_value = '';
  public $min_value = '';
  public $popup_anchor = '';
  public $start_date;
  public $tab_index;

  protected function typeof_name        () {
    return AttributeType::ID;
  }
  protected function typeof_value       () {
    return AttributeType::TEXT;
  }
  protected function typeof_type        () {
    return AttributeType::ID;
  }
  protected function enum_type          () {
    return array('line','multiline','password','date','number');
  }
  protected function typeof_autofocus       () {
    return AttributeType::BOOL;
  }
  protected function typeof_autocomplete () {
    return AttributeType::BOOL;
  }
  protected function typeof_read_only   () {
    return AttributeType::BOOL;
  }
  protected function typeof_autoselect () {
    return AttributeType::BOOL;
  }
  protected function typeof_on_change   () {
    return AttributeType::TEXT;
  }
  protected function typeof_action () {
    return AttributeType::TEXT;
  }
  protected function typeof_date_format () {
    return AttributeType::TEXT;
  }
  protected function typeof_max_value () {
    return AttributeType::NUM;
  }
  protected function typeof_min_value () {
    return AttributeType::NUM;
  }
  protected function typeof_popup_anchor () {
    return AttributeType::ID;
  }
  protected function typeof_start_date () {
    return AttributeType::TEXT;
  }
  protected function typeof_tab_index () {
    return AttributeType::NUM;
  }
}

class Input extends VisualComponent {

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return InputAttributes
   */
  public function attrs() {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return InputAttributes
   */
  public function newAttributes() {
    return new InputAttributes($this);
  }

  protected function preRender() {
      if ($this->attrs()->type == 'date') {
        $cal = new Calendar($this->context);
        $cal->attachTo($this);
        $cal->detach();
      }
    $type = $this->attrs()->get('type', 'line');
    switch($type) {
      case 'multiline':
        $this->containerTag = 'textarea';
        break;
      default:
        $this->containerTag = 'input';
        $this->cssClassName .= "type-$type";
    }
    if ($this->attrs()->read_only)
      $this->cssClassName .= 'readonly';
    parent::preRender();
  }

  protected function render() {
    $type = $this->attrs()->get('type', 'line');
    $name = $this->attrs()->name;
    $action = ifset($this->attrs()->action,"checkKeybAction(event,'".$this->attrs()->action."')");

    switch($type) {
      case 'multiline':
        $this->addAttributes([
                'name'     => $name,
                'cols'     => 0,
                'readonly' => $this->attrs()->read_only ? 'readonly' : NULL,
                'disabled' => $this->attrs()->disabled ? 'disabled' : NULL,
                'tabindex' => $this->attrs()->tab_index,
                'onclick'  => $this->attrs()->autoselect ? 'this.select()' : NULL,
                'onchange' => $this->attrs()->on_change,
                'spellcheck' => 'false',
                ]);
          $this->setContent($this->attrs()->value);
        break;
      case 'line':
        $type = 'text';
        // no break
      default:
      $this->addAttributes([
                'type'       => $type == 'number' ? 'text' : $type,
                'name'       => $name,
                'value'      => $this->attrs()->value,
                'readonly'   => $this->attrs()->read_only ? 'readonly' : NULL,
                'autocomplete' => $this->attrs()->autocomplete ? NULL : 'off',
                'disabled'   => $this->attrs()->disabled ? 'disabled' : NULL,
                'tabindex'   => $this->attrs()->tab_index,
                'onclick'    => $this->attrs()->autoselect ? 'this.select()' : NULL,
                'onchange'   => $this->attrs()->on_change,
                'onkeypress' => $action
        ]);
    }
  }

}