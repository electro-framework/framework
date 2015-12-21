<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class Head extends Component implements PropertiesInterface
{
  public $allowsChildren = true;

  /**
   * Returns the component's properties.
   * @return ComponentProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Creates an instance of the component's properties.
   * @return ComponentProperties
   */
  public function newProperties ()
  {
    return new ComponentProperties($this);
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

