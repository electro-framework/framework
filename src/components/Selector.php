<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\Component;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\components\Literal;
use impactwave\matisse\exceptions\ComponentException;
use impactwave\matisse\VisualComponent;

class SelectorAttributes extends ComponentAttributes {
  public $name;
  public $value;
  public $values;
  public $value_field = '0';
  public $label_field = '1';
  public $list_item;
  public $data;
  public $autofocus = FALSE;
  public $empty_selection = FALSE;
  public $auto_select_first = FALSE;
  public $load_linked_on_init = TRUE;
  public $empty_label = '';
  public $on_change;
  public $source_url; //use $name in the URL to bind to the value of the $name field, otherwhise the linked value will be appended
  public $linked_selector;
  public $auto_open_linked;
  public $multiple = FALSE;


  protected function typeof_name            () { return AttributeType::ID; }
  protected function typeof_value           () { return AttributeType::TEXT; }
  protected function typeof_values          () { return AttributeType::DATA; }
  protected function typeof_value_field     () { return AttributeType::ID; }
  protected function typeof_label_field     () { return AttributeType::ID; }
  protected function typeof_list_item       () { return AttributeType::SRC; }
  protected function typeof_data            () { return AttributeType::DATA; }
  protected function typeof_autofocus           () { return AttributeType::BOOL; }
  protected function typeof_empty_selection () { return AttributeType::BOOL; }
  protected function typeof_empty_label     () { return AttributeType::TEXT; }
  protected function typeof_auto_select_first() { return AttributeType::BOOL; }
  protected function typeof_load_linked_on_init() { return AttributeType::BOOL; }
  protected function typeof_on_change       () { return AttributeType::TEXT; }
  protected function typeof_source_url      () { return AttributeType::TEXT; }
  protected function typeof_linked_selector () { return AttributeType::ID; }
  protected function typeof_auto_open_linked() { return AttributeType::BOOL; }
  protected function typeof_multiple        () { return AttributeType::BOOL; }
}

class Selector extends VisualComponent
{
  protected $autoId = true;

  protected $containerTag = 'select';

  /**
   * Returns the component's attributes.
   * @return SelectorAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return SelectorAttributes
   */
  public function newAttributes ()
  {
    return new SelectorAttributes($this);
  }

  private $selectedLabel;

  protected function render ()
  {
    $isMultiple = $this->attrs ()->multiple;
    $this->addAttribute('name',$this->attrs ()->name);
    $this->addAttributeIf($isMultiple, 'multiple', '');
    $this->addAttributeIf($this->attrs()->on_change, 'onchange', $this->attrs()->on_change);
    $this->beginContent ();
    if ($this->attrs ()->empty_selection) {
      $sel = exists ($this->attrs ()->value) ? '' : ' selected';
      echo '<option value=""' . $sel . '>' . $this->attrs ()->empty_label . '</option>';
    }
    $this->defaultDataSource = $this->attrs ()->get ('data');
    if (isset($this->defaultDataSource)) {
      $dataIter = $this->defaultDataSource->getIterator ();
      $dataIter->rewind ();
      if ($dataIter->valid ()) {
        $template = $this->attrs ()->get ('list_item');
        if (isset($template)) {
          do {
            $template->value = $this->evalBinding ('{' . $this->attrs ()->value_field . '}');
            Component::renderSet ($template);
            $dataIter->next ();
          } while ($dataIter->valid ());
        } else {
          $selValue = strval ($this->attrs ()->get ('value'));
          if ($isMultiple) {
            $values = $this->attrs ()->values;
            if (method_exists($values, 'getIterator')) {
              $it = $values->getIterator();
              if (!$it->valid())
                $values = [];
              else $values = iterator_to_array($it);
            }
            if (!is_array ($values))
              throw new ComponentException($this,
                "Value of multiple selection component must be an array; " . gettype ($values) .
                " was given, with value: " . print_r ($values, true));
          }
          $first = true;
          do {
            $label = $this->evalBinding ('{' . $this->attrs ()->label_field . '}');
            $value = strval ($this->evalBinding ('{' . $this->attrs ()->value_field . '}'));
            if ($first && !$this->attrs ()->empty_selection && $this->attrs ()->auto_select_first &&
              !exists ($selValue)
            )
              $this->attrs ()->value = $selValue = $value;
            if (!strlen ($label))
              $label = $this->attrs ()->empty_label;

            if ($isMultiple) {
              $sel = array_search($value, $values) !== false  ? ' selected' : '';
            }
            else {
              if ($value === $selValue)
                $this->selectedLabel = $label;
              $sel = $value === $selValue ? ' selected' : '';
            }
            echo "<option value=\"$value\"$sel>$label</option>";
            $dataIter->next ();
            $first = false;
          } while ($dataIter->valid ());
        }
      }
    }
  }

}

