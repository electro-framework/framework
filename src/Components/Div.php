<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\VisualComponent;

class DivAttributes extends ComponentAttributes
{
  public $content;

  protected function typeof_content () { return AttributeType::SRC; }

}

class Div extends VisualComponent
{

  public $defaultAttribute = 'content';

  /**
   * Returns the component's attributes.
   * @return DivAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return DivAttributes
   */
  public function newAttributes ()
  {
    return new DivAttributes($this);
  }

  protected function render ()
  {
    $this->beginContent();
    $this->renderSet ($this->getChildren ('content'));
  }

}
