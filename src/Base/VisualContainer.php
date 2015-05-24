<?php
namespace Selene\Matisse\Base;
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\VisualComponent;

class VisualContainerAttributes extends ComponentAttributes
{
  public $content;

  protected function typeof_content () { return AttributeType::SRC; }
}

class VisualContainer extends VisualComponent
{

  public $defaultAttribute = 'content';

  /**
   * Returns the component's attributes.
   * @return VisualContainerAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return VisualContainerAttributes
   */
  public function newAttributes ()
  {
    return new VisualContainerAttributes($this);
  }

  protected function render ()
  {
    $this->beginContent ();
    $this->renderSet ($this->getChildren ('content'));
  }

}
