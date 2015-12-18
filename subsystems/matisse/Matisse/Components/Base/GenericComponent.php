<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Attributes\Base\GenericAttributes;
use Selenia\Matisse\Parser\Context;

class GenericComponent extends VisualComponent
{
  public function __construct (Context $context, $tagName, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  /**
   * Returns the component's attributes.
   * @return GenericAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return GenericAttributes
   */
  public function newAttributes ()
  {
    return new GenericAttributes($this);
  }

  protected function postRender ()
  {
  }

  protected function preRender ()
  {
  }

  protected function render ()
  {
    $this->begin ($this->getTagName ());
    $attrs = $this->attrs ()->getAll ();
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
