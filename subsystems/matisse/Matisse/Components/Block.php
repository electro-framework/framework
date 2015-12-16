<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\IAttributes;

class BlockAttributes extends ComponentAttributes
{
  public $name;
  public $replace = false;
  public $yield;

  protected function typeof_content () { return AttributeType::SRC; }

  protected function typeof_name () { return AttributeType::ID; }

  protected function typeof_replace () { return AttributeType::BOOL; }

  protected function typeof_yield () { return AttributeType::ID; }

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
class Block extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Returns the component's attributes.
   * @return BlockAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return BlockAttributes
   */
  public function newAttributes ()
  {
    return new BlockAttributes($this);
  }

  /**
   * Adds the content of the `content` parameter to a named block on the page.
   */
  protected function render ()
  {
    $attr = $this->attrs ();
    if (strlen ($attr->yield)) {
      $block = $this->page->getBlock ($attr->yield);
      $this->renderExternal ($block);
    }
    $content = $this->removeChildren ();
    if (!strlen ($attr->name))
      throw new ComponentException($this, "<kbd>name</kbd> attribute is not set.");
    if ($attr->replace)
      $this->page->setBlock ($attr->name, $content);
    else $this->page->appendToBlock ($attr->name, $content);
  }
}

