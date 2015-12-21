<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

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
class Block extends Component implements PropertiesInterface
{
  public $allowsChildren = true;

  /**
   * Returns the component's properties.
   * @return BlockProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Creates an instance of the component's properties.
   * @return BlockProperties
   */
  public function newProperties ()
  {
    return new BlockProperties($this);
  }

  /**
   * Adds the content of the `content` parameter to a named block on the page.
   */
  protected function render ()
  {
    $attr = $this->props ();
    if (strlen ($attr->yield)) {
      $block = $this->page->getBlock ($attr->yield);
      $this->attachAndRenderSet ($block);
    }
    $content = $this->removeChildren ();
    if (!strlen ($attr->name))
      throw new ComponentException($this, "<kbd>name</kbd> attribute is not set.");
    if ($attr->replace)
      $this->page->setBlock ($attr->name, $content);
    else $this->page->appendToBlock ($attr->name, $content);
  }
}

