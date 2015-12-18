<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\IAttributes;

class Head extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Returns the component's attributes.
   * @return ComponentAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ComponentAttributes
   */
  public function newAttributes ()
  {
    return new ComponentAttributes($this);
  }

  /**
   * Adds the content of the `content` parameter to the page's head element.
   */
  protected function render ()
  {
    $html = $this->getContent ();
    if ($html != '')
      $this->page->extraHeadTags .= $html;
  }
}

