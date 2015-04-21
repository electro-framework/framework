<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class TabAttributes extends ComponentAttributes
{
  public $name;
  public $value;
  public $label;
  public $url;
  public $disabled = false;
  public $selected = false;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_label () { return AttributeType::TEXT; }
  protected function typeof_url () { return AttributeType::TEXT; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_selected () { return AttributeType::BOOL; }
}

class Tab extends VisualComponent
{

  /**
   * The id of the containing Tabs component, if any.
   * @var string
   */
  public  $container_id;
  private $fixIE6 = false;

  /**
   * Returns the component's attributes.
   * @return TabAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return TabAttributes
   */
  public function newAttributes ()
  {
    return new TabAttributes($this);
  }

  protected function preRender ()
  {
    //$this->fixIE6 = !isset($this->style()->width) && $this->page->browserIsIE6;
    $this->beginTag ($this->fixIE6 ? 'table' : 'div');
    $this->addAttribute ('id', $this->attrs ()->id);
    $this->addAttribute ('class', enum (' ', $this->className, $this->attrs ()->class, $this->attrs ()->css_class
    ));
    if ($this->fixIE6) {
      $this->beginTag ('tr');
      $this->beginTag ('td');
    }
  }

  protected function postRender ()
  {
    if ($this->fixIE6) {
      $this->endTag ();
      $this->endTag ();
    }
    $this->endTag ();
  }

  protected function render ()
  {
    $this->beginTag ('div');
    $this->addAttribute ('class',
      enum (' ', $this->attrs ()->disabled ? 'disabled' : '', $this->attrs ()->selected ? 'selected' : ''
      ));

    $this->beginTag ('input');
    $this->addAttribute ('type', 'radio');
    $this->addAttribute ('name', $this->attrs ()->name);
    $this->addAttribute ('value', $this->attrs ()->value);
    if (!isset($this->attrs ()->id))
      $this->attrs ()->id = 'tab' . $this->getUniqueId ();
    $this->addAttribute ('id', "{$this->attrs()->id}Field");
    $this->addAttributeIf ($this->attrs ()->disabled, 'disabled', 'disabled');
    $this->addAttributeIf ($this->attrs ()->selected, 'checked', 'checked');
    $this->endTag ();

    $this->beginTag ('label');
    $this->addAttribute ('for', "{$this->attrs()->id}Field");
    $this->addAttribute ('hidefocus', '1');
    $this->addAttribute ('onclick', 'Tab_change(this' . (isset($this->container_id) ? ",'$this->container_id'" : '') .
                                    (isset($this->attrs ()->url) ? ",'{$this->attrs()->url}')" : ')'));

    $this->beginTag ('span');
    $this->addAttribute ('class', 'text');
    $this->addAttribute ('unselectable', 'on');
    /*
        if (isset($this->style()->icon)) {
          $this->beginTag('img');
          switch ($this->style()->icon_align) {
            case NULL:
            case 'left':
              $this->addAttribute('class', 'icon icon_left');
              break;
            case 'right':
              $this->addAttribute('class', 'icon icon_right');
              break;
            default:
              $this->addAttribute('class', 'icon');
              break;
          }
          $this->addAttribute('src', $this->style()->propertyToImageURI('icon'));
          $this->endTag();
        }
    */
    $this->setContent ($this->attrs ()->label);
    $this->endTag ();

    $this->endTag ();

    $this->endTag ();
  }

}

