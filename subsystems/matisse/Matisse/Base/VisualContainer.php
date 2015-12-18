<?php
namespace Selenia\Matisse\Base;
use Selenia\Matisse\Attributes\VisualComponentAttributes;
use Selenia\Matisse\Type;
use Selenia\Matisse\VisualComponent;

/**
 * Visual containers are components that wrap their content with other markup.
 *
 * <p>You may specify the content directly as the component tag's content, or via a specific subtag (`<Content>` by
 * default).
 * <p>The subtag is useful on situations where you need to disambiguate the content (because of tag name clashes, for
 * ex.),
 */
class VisualContainerAttributes extends VisualComponentAttributes
{
  public $content;

  protected function typeof_content () { return Type::SRC; }
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
    $this->renderChildren ($this->hasChildren () ? null : $this->defaultAttribute);
  }

}
