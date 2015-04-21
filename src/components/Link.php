<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class LinkAttributes extends ComponentAttributes
{
  public $label;
  public $url;
  public $disabled = false;
  public $tooltip;
  public $script;
  public $action;
  public $param;

  protected function typeof_label () { return AttributeType::TEXT; }
  protected function typeof_url () { return AttributeType::TEXT; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_tooltip () { return AttributeType::TEXT; }
  protected function typeof_script () { return AttributeType::TEXT; }
  protected function typeof_action () { return AttributeType::ID; }
  protected function typeof_param () { return AttributeType::TEXT; }
}

class Link extends VisualComponent
{

  /** overriden */
  protected $containerTag = 'a';

  /**
   * Returns the component's attributes.
   * @return LinkAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return LinkAttributes
   */
  public function newAttributes ()
  {
    return new LinkAttributes($this);
  }

  protected function render ()
  {
    $script = $this->attrs ()->action ? "doAction('{$this->attrs()->action}','{$this->attrs()->param}')"
      : $this->attrs ()->script;

    $this->addAttribute ('title', $this->attrs ()->tooltip);
    $this->addAttribute ('href', $this->attrs ()->disabled
      ? '#'
      :
      (isset($this->attrs ()->url)
        ?
        $this->attrs ()->url
        :
        "javascript:$script"
      )
    );
    $this->beginContent ();
    $this->setContent ($this->attrs ()->label);
  }
}

