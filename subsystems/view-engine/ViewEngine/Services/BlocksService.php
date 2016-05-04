<?php
namespace Selenia\ViewEngine\Services;

use Selenia\ViewEngine\Lib\Block;

/**
 * Manages blocks of document fragments.
 *
 * > <p>Blocks are lazily evaluated, so they are not rendered when being defined.
 */
class BlocksService
{
  /**
   * A map of block names => block contents.
   * > This must be public to be accessible for databiding.
   *
   * @var Block[]
   */
  private $blocks = [];

  /**
   * Returns the content of a specific block.
   *
   * @param string $name An arbitrary block name.
   * @returns Block
   */
  function getBlock ($name)
  {
    return get ($this->blocks, $name) ?: $this->blocks[$name] = new Block;
  }

  /**
   * Checks if a block with the specified name exists and has content.
   *
   * @param string $name An arbitrary block name.
   * @return bool
   */
  function hasBlock ($name)
  {
    return isset ($this->blocks[$name]);
  }

}
