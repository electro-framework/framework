<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Properties\Base\HtmlContainerProperties;

class HtmlContainer extends HtmlComponent
{
  protected static $propertiesClass = HtmlContainerProperties::class;

  public $defaultProperty = 'content';

  protected function render ()
  {
    $this->beginContent ();
    $this->renderChildren ($this->hasChildren () ? null : $this->defaultProperty);
  }

}
