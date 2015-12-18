<?php
namespace Selenia\Matisse\Base;

use Selenia\Matisse\Attributes\GenericAttributes;
use Selenia\Matisse\Context;
use Selenia\Matisse\VisualComponent;

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
    $this->beginTag ($this->getTagName ());
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
    $this->endTag ();
  }

}
