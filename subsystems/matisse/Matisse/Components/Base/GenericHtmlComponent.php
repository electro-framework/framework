<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\GenericProperties;

class GenericHtmlComponent extends HtmlComponent
{
  protected static $propertiesClass = GenericProperties::class;

  public function __construct (Context $context, $tagName, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  protected function postRender ()
  {
  }

  protected function preRender ()
  {
  }

  /**
   * Returns the component's properties.
   * @return GenericProperties
   */
  public function props ()
  {
    return $this->props;
  }

  protected function render ()
  {
    $this->begin ($this->getTagName ());
    $attrs = $this->props ()->getAll ();
    foreach ($attrs as $k => $v) {
      if (isset($v) && $v !== '' && is_scalar ($v)) {
        if (is_bool ($v)) {
          if ($v) echo " $k";
        }
        else echo " $k=\"$v\"";
      }
    }
    $this->beginContent ();
    $this->renderContent ();
    $this->end ();
  }

}
