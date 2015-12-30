<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class BlockProperties extends ComponentProperties
{
  /**
   * @var string
   */
  public $name = type::id;
  /**
   * @var bool
   */
  public $replace = false;
  /**
   * @var string
   */
  public $yield = type::id;
}

/**
 * The Block component allows one to append HTML to named memory containers, and yield them later at specific locations.
 *
 * <p>Ex:
 * <p>
 * ```HTML
 *   <Block name="header">
 *     <h1>A Header</h1>
 *   </Block>
 *
 *   <Block yield="header"/>
 * ```
 */
class Block extends Component
{
  protected static $propertiesClass = BlockProperties::class;

  public $allowsChildren = true;
  /** @var BlockProperties */
  public $props;

  /**
   * Adds the content of the `content` parameter to a named block on the page.
   */
  protected function render ()
  {
    $prop = $this->props;
    if (strlen ($prop->yield)) {
      $block = $this->page->getBlock ($prop->yield);
      $this->attachAndRenderSet ($block);
      return;
    }
    $content = $this->removeChildren ();
    if (!strlen ($prop->name))
      throw new ComponentException($this, "<kbd>name</kbd> attribute is not set.");
    if ($prop->replace)
      $this->page->setBlock ($prop->name, $content);
    else $this->page->appendToBlock ($prop->name, $content);
  }
}

