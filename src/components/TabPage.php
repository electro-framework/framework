<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class TabPageAttributes extends ComponentAttributes
{
  public $id;
  public $label;
  public $icon;
  public $selected;
  public $content;
  public $index;
  public $value;
  public $url; //used by Tabs
  public $disabled;
  public $lazy_creation = false;

  protected function typeof_id () { return AttributeType::ID; }
  protected function typeof_label () { return AttributeType::TEXT; }
  protected function typeof_icon () { return AttributeType::TEXT; }
  protected function typeof_selected () { return AttributeType::BOOL; }
  protected function typeof_content () { return AttributeType::SRC; }
  protected function typeof_index () { return AttributeType::NUM; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_url () { return AttributeType::TEXT; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_lazy_creation () { return AttributeType::BOOL; }
}

class TabPage extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return TabPageAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return TabPageAttributes
   */
  public function newAttributes ()
  {
    return new TabPageAttributes($this);
  }

  protected function render ()
  {
    if (!$this->parent || $this->parent->className != 'Tabs')
      throw new ComponentException($this, 'TabPages may only exist inside Tabs components.');
    $this->children = $this->attrs ()->content->children;
    if ($this->attrs ()->lazy_creation) {
      ob_start ();
      $this->renderChildren ();
      $html   = ob_get_clean ();
      $html   = str_replace ('\\', '\\\\', $html);
      $html   = str_replace ("'", "\\'", $html);
      $html   = str_replace (chr (0xE2) . chr (0x80) . chr (0xA8), '\n', $html);
      $html   = str_replace (chr (0xE2) . chr (0x80) . chr (0xA9), '\n', $html);
      $html   = str_replace ("\r", '', $html);
      $html   = str_replace ("\n", '\n', $html);
      $html   = str_replace ('</script>', "</s'+'cript>", $html);
      $script = "var {$this->attrs()->id}Content='$html';";
      $this->page->addInlineScript ($script);
    } else {
      $this->beginContent ();
      $this->renderChildren ();
    }
  }

}

