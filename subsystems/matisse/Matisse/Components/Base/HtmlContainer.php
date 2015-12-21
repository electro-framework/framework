<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Properties\Base\HtmlComponentProperties;
use Selenia\Matisse\Properties\Types\type;

/**
 * Html containers are components that wrap their content with other markup.
 *
 * <p>You may specify the content directly as the component tag's content, or via a specific subtag (`<Content>` by
 * default).
 * <p>The subtag is useful on situations where you need to disambiguate the content (because of tag name clashes, for
 * ex.),
 */
class HtmlContainerProperties extends HtmlComponentProperties
{
  /**
   * @var ContentProperty|null
   */
  public $content = type::content;

}

class HtmlContainer extends HtmlComponent
{
  public $defaultAttribute = 'content';

  /**
   * Returns the component's properties.
   * @return HtmlContainerProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Creates an instance of the component's properties.
   * @return HtmlContainerProperties
   */
  public function newProperties ()
  {
    return new HtmlContainerProperties($this);
  }

  protected function render ()
  {
    $this->beginContent ();
    $this->renderChildren ($this->hasChildren () ? null : $this->defaultAttribute);
  }

}
