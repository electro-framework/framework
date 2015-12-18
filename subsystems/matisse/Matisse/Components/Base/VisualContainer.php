<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Attributes\Base\VisualComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Internal\Parameter;

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
  /**
   * @var Parameter|null
   */
  public $content = type::parameter;

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
