<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class CheckboxAttributes extends ComponentAttributes
{
  public $name;
  public $label;
  public $value    = 1;
  public $disabled = false;
  public $checked  = false;
  public $autofocus    = false;
  public $tooltip;
  public $script;
  public $test_value;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_label () { return AttributeType::TEXT; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_checked () { return AttributeType::BOOL; }
  protected function typeof_autofocus () { return AttributeType::BOOL; }
  protected function typeof_tooltip () { return AttributeType::TEXT; }
  protected function typeof_script () { return AttributeType::TEXT; }
  protected function typeof_test_value () { return AttributeType::TEXT; }
}

class Checkbox extends VisualComponent
{
  protected $autoId = true;

  protected $containerTag = 'div';

  /**
   * Returns the component's attributes.
   * @return CheckboxAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return CheckboxAttributes
   */
  public function newAttributes ()
  {
    return new CheckboxAttributes($this);
  }

  protected function render ()
  {

    //if (isset($this->style()->icon) && $this->style()->icon_align == 'left')
    //    $this->renderIcon();

    $this->beginTag ('input');
    $this->addAttribute ('id', "{$this->attrs ()->id}Field");
    $this->addAttribute ('type', 'checkbox');
    $this->addAttribute ('value', $this->attrs ()->get ('value'));
    $this->addAttribute ('name', $this->attrs ()->name);
    $this->addAttributeIf ($this->attrs ()->checked ||
                           (isset($this->attrs ()->test_value) &&
                            $this->attrs ()->value == $this->attrs ()->test_value), 'checked',
      'checked');
    $this->addAttributeIf ($this->attrs ()->disabled, 'disabled', 'disabled');
    $this->addAttribute ('onclick', $this->attrs ()->script);
    $this->endTag ();

    //if (isset($this->style()->icon) && $this->style()->icon_align == 'center')
    //    $this->renderIcon();

    $this->beginTag ('label');
    $this->addAttribute ('for', "{$this->attrs ()->id}Field");
    $this->endTag ();

    if (isset($this->attrs ()->label)) {
      $this->endTag ();
      $this->beginTag ('label');
      $this->addAttribute ('for', "{$this->attrs ()->id}Field");
      $this->addAttribute ('title', $this->attrs ()->tooltip);
      $this->setContent ($this->attrs ()->label);
    }

    //if (isset($this->style()->icon) && $this->style()->icon_align == 'right')
    //    $this->renderIcon();
  }
  /*
      private function renderIcon() {
          $this->beginTag('img',array(
              'class' => 'icon icon_'.$this->style()->icon_align,
              'src'   => $this->style()->icon
          ));
          $this->endTag();
      }*/
}
