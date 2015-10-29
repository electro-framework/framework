<?php
namespace Selenia\Matisse\Base;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Context;
use Selenia\Matisse\Attributes\GenericAttributes;
use Selenia\Matisse\VisualComponent;

class GenericComponentAttributes extends GenericAttributes
{
  public $content;

  protected function typeof_content () { return AttributeType::SRC; }

}

class GenericComponent extends VisualComponent
{

  public $defaultAttribute = 'content';

  public function __construct (Context $context, $tagName, array $attributes = null)
  {
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  /**
   * Returns the component's attributes.
   * @return GenericComponentAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return GenericComponentAttributes
   */
  public function newAttributes ()
  {
    return new GenericComponentAttributes($this);
  }

  protected function preRender ()
  {
  }

  protected function postRender ()
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
    self::renderSet ($this->getChildren ('content'));
    $this->endTag ();
  }

}
