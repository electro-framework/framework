<?php
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\VisualComponent;

class InputAttributes extends ComponentAttributes
{
  public $name;
  public $value;
  public $type;
  public $autofocus    = false;
  public $read_only    = false;
  public $autoselect   = false;
  public $autocomplete = true;
  public $on_change;
  public $action       = '';
  public $date_format  = 'YYYY-MM-DD';
  public $max_value    = '';
  public $min_value    = '';
  public $popup_anchor = '';
  public $start_date;
  public $tab_index;

  protected function typeof_name ()
  {
    return AttributeType::ID;
  }

  protected function typeof_value ()
  {
    return AttributeType::TEXT;
  }

  protected function typeof_type ()
  {
    return AttributeType::ID;
  }

  protected function enum_type ()
  {
    return ['line', 'multiline', 'password', 'date', 'number'];
  }

  protected function typeof_autofocus ()
  {
    return AttributeType::BOOL;
  }

  protected function typeof_autocomplete ()
  {
    return AttributeType::BOOL;
  }

  protected function typeof_read_only ()
  {
    return AttributeType::BOOL;
  }

  protected function typeof_autoselect ()
  {
    return AttributeType::BOOL;
  }

  protected function typeof_on_change ()
  {
    return AttributeType::TEXT;
  }

  protected function typeof_action ()
  {
    return AttributeType::TEXT;
  }

  protected function typeof_date_format ()
  {
    return AttributeType::TEXT;
  }

  protected function typeof_max_value ()
  {
    return AttributeType::NUM;
  }

  protected function typeof_min_value ()
  {
    return AttributeType::NUM;
  }

  protected function typeof_popup_anchor ()
  {
    return AttributeType::ID;
  }

  protected function typeof_start_date ()
  {
    return AttributeType::TEXT;
  }

  protected function typeof_tab_index ()
  {
    return AttributeType::NUM;
  }
}

class Input extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return InputAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return InputAttributes
   */
  public function newAttributes ()
  {
    return new InputAttributes($this);
  }

  protected function preRender ()
  {
    if ($this->attrs ()->type == 'date') {
      $cal = new Calendar($this->context);
      $cal->attachTo ($this);
      $cal->detach ();
    }
    $type = $this->attrs ()->get ('type', 'line');
    switch ($type) {
      case 'multiline':
        $this->containerTag = 'textarea';
        break;
      default:
        $this->containerTag = 'input';
        $this->cssClassName .= "type-$type";
    }
    if ($this->attrs ()->read_only)
      $this->cssClassName .= 'readonly';
    parent::preRender ();
  }

  protected function render ()
  {
    $attr = $this->attrs ();
    /** @var Controller $controller */
    $controller = $this->page->controller;
    $type       = $attr->get ('type', 'line');
    $name       = $attr->name;
    $action     = ifset ($attr->action, "checkKeybAction(event,'" . $attr->action . "')");

    switch ($type) {
      case 'multiline':
        $this->addAttributes ([
          'name'       => $name,
          'cols'       => 0,
          'readonly'   => $attr->read_only ? 'readonly' : null,
          'disabled'   => $attr->disabled ? 'disabled' : null,
          'tabindex'   => $attr->tab_index,
          'onfocus'    => $attr->autoselect ? 'this.select()' : null,
          'onchange'   => $attr->on_change,
          'spellcheck' => 'false',
        ]);
        $this->setContent ($attr->value);
        break;
      case 'date':
      case 'datetime':
        $this->page->addScript('modules/admin/js/moment-with-locales.min.js');
        $this->page->addScript('modules/admin/js/bootstrap-datetimepicker.min.js');
        $this->page->addStylesheet('modules/admin/css/bootstrap-datetimepicker.min.css');

        $this->addAttributes ([
          'type'       => 'text',
          'name'       => $name,
          'value'      => $attr->value,
          'readonly'   => $attr->read_only ? 'readonly' : null,
          'disabled'   => $attr->disabled ? 'disabled' : null,
          'tabindex'   => $attr->tab_index,
          'onfocus'    => $attr->autoselect ? 'this.select()' : null,
          'onchange'   => $attr->on_change,
          'onkeypress' => $action
        ]);
        $hasTime = boolToStr ($type == 'datetime');
        $this->beginContent();
        echo <<<HTML
<script type="text/javascript">
$(function () {
  $('#{$name}0').datetimepicker({
    locale:      '$controller->lang',
    defaultDate: '$attr->value' || new moment(),
    format:      '$attr->date_format',
    sideBySide:  $hasTime,
    showTodayButton: true,
    showClear: true,
    showClose: true
  });
});
</script>
HTML;
        break;
      case 'line':
        $type = 'text';
      // no break
      default:
        $this->addAttributes ([
          'type'         => $type == 'number' ? 'text' : $type,
          'name'         => $name,
          'value'        => $attr->value,
          'readonly'     => $attr->read_only ? 'readonly' : null,
          'autocomplete' => $attr->autocomplete ? null : 'off',
          'disabled'     => $attr->disabled ? 'disabled' : null,
          'tabindex'     => $attr->tab_index,
          'onfocus'      => $attr->autoselect ? 'this.select()' : null,
          'onchange'     => $attr->on_change,
          'onkeypress'   => $action
        ]);
    }
  }

}