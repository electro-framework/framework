<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class Head extends Component
{
  protected static $propertiesClass = ComponentProperties::class;

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
   * Adds the content of the `content` parameter to the page's head element.
   */
  protected function render ()
  {
    $html = $this->getContent ();
    if ($html != '')
      $this->page->extraHeadTags .= $html;
  }
}

