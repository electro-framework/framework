<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class ButtonAttributes extends ComponentAttributes
{
  public $action;
  public $param;
  public $script;
  public $url;
  public $label;
  public $message;
  public $confirm = false;
  public $help;
  public $tab_index;
  public $icon;

  protected function typeof_action () { return AttributeType::ID; }
  protected function typeof_param () { return AttributeType::TEXT; }
  protected function typeof_script () { return AttributeType::TEXT; }
  protected function typeof_url () { return AttributeType::TEXT; }
  protected function typeof_label () { return AttributeType::TEXT; }
  protected function typeof_message () { return AttributeType::TEXT; }
  protected function typeof_confirm () { return AttributeType::BOOL; }
  protected function typeof_help () { return AttributeType::TEXT; }
  protected function typeof_tab_index () { return AttributeType::NUM; }
  protected function typeof_icon () { return AttributeType::TEXT; }
}

class Button extends VisualComponent
{

  public $cssClassName = 'btn';

  /** overriden */
  protected $containerTag = 'button';

  /**
   * Returns the component's attributes.
   * @return ButtonAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ButtonAttributes
   */
  public function newAttributes ()
  {
    return new ButtonAttributes($this);
  }

  protected function preRender ()
  {
    if (isset($this->attrs ()->icon))
      $this->cssClassName .= ' with-icon';
    parent::preRender ();
  }

  protected function render ()
  {
    $actionData = '';
    if ($this->attrs ()->disabled)
      $this->addAttribute ('disabled', 'disabled');
    $this->addAttributeIf ($this->attrs ()->tab_index, 'tabindex', $this->attrs ()->tab_index);
    $this->addAttribute ('type', 'button');
    if ($this->page->browserIsIE)
      $this->addAttribute ('hideFocus', 'true');
    if (isset($this->attrs ()->action)) {
      if (isset($this->attrs ()->param))
        $action = $this->attrs ()->action . ':' . $this->attrs ()->param;
      else $action = $this->attrs ()->action;
      //if ($this->page->browserIsIE) $actionData = "<!--$action-->";
      //else $this->addAttribute('value',$action);
      $this->beginAttribute ('onclick', null, ';');
      if ($this->attrs ()->confirm)
        $this->addAttributeValue ("Button_onConfirm('{$action}','{$this->attrs()->message}')");
      else $this->addAttributeValue ("doAction('" . $action . "')");

      $this->endAttribute ();
    } else {
      if (isset($this->attrs ()->script))
        $this->addAttribute ('onclick', $this->attrs ()->script);
      else if (isset($this->attrs ()->url))
        $this->addAttribute ('onclick', "go('{$this->attrs()->url}',event);");
    }
    if (isset($this->attrs ()->help))
      $this->addAttribute ('title', $this->attrs ()->help);

    $this->beginContent ();

    if (isset($this->attrs ()->icon)) {
      $this->addTag ('i', [
        'class' => $this->attrs ()->icon
      ]);
    }

    $txt = trim ($this->attrs ()->label . $actionData);
    echo strlen ($txt) ? $txt : '&nbsp;';

  }
}
