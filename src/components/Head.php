<?php
use selene\matisse\AttributeType;
use selene\matisse\ComponentAttributes;
use selene\matisse\Component;
use selene\matisse\components\Parameter;
use selene\matisse\IAttributes;

class HeadAttributes extends ComponentAttributes
{
  public $content;

  protected function typeof_content () { return AttributeType::SRC; }
}

class Head extends Component implements IAttributes
{
  public $defaultAttribute = 'content';

  /**
   * Returns the component's attributes.
   * @return HeadAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return HeadAttributes
   */
  public function newAttributes ()
  {
    return new HeadAttributes($this);
  }

  /**
   * Adds the content of the `content` parameter to the page's head element.
   */
  protected function render ()
  {
    /** @var Parameter $content */
    $content = $this->attrs ()->content;
    ob_start();
    self::renderSet($content->children);
    $html = ob_get_clean ();
    if (!empty($html))
      $this->page->extraHeadTags .= $html;
  }
}

