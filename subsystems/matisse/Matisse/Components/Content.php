<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class ContentProperties extends ComponentProperties
{
  /**
   * The block name. If you set it via this property, the new content will be appended to the saved content (if any).
   *
   * @var string
   */
  public $appendTo = type::id;
  /**
   * Modifies the saved content only if none is set yet.
   *
   * @var bool
   */
  public $byDefault = false;
  /**
   * The block name. If you set it via this property, the new content will overwrite the saved content (if any).
   *
   * @var string
   */
  public $of = type::id;
  /**
   * The block name. If you set it via this property, the new content will be prepended to the saved content (if any).
   *
   * @var string
   */
  public $prependTo = type::id;
}

/**
 * The Content component allows you to save HTML on named memory containers, and yield it later at specific
 * locations.
 *
 * <p>Ex:
 * <p>
 * ```HTML
 *   <Content of="header">
 *     <h1>A Header</h1>
 *   </Content>
 *
 *   {!! #header !!}
 * ```
 * <p>You can also use the `{{ #name }}` syntax, but note that it escapes its output, which is, usually, not what
 * you intend, as the content being output is (or should be) already safe HTML.
 */
class Content extends Component
{
  protected static $propertiesClass = ContentProperties::class;

  public $allowsChildren = true;
  /** @var ContentProperties */
  public $props;

  /**
   * Adds the content of the `content` parameter to a named block on the page.
   */
  protected function render ()
  {
    $prop = $this->props;

    if (exists ($name = $prop->of)) {
      if ($prop->byDefault && $this->context->hasBlock ($name))
        return;
      $this->context->setBlock ($name, $this->getContent ());
    }
    elseif (exists ($name = $prop->appendTo)) {
      if ($prop->byDefault && $this->context->hasBlock ($name))
        return;
      $this->context->appendToBlock ($name, $this->getContent ());
    }
    elseif (exists ($name = $prop->prependTo)) {
      if ($prop->byDefault && $this->context->hasBlock ($name))
        return;
      $this->context->prependToBlock ($name, $this->getContent ());
    }
    else throw new ComponentException($this,
      "One of these properties must be set:<p><kbd>of | appendTo | prependTo</kbd>");
  }

}

