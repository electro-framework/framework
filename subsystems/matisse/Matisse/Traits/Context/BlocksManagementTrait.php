<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Text;

/**
 * Manages blocks of document fragments.
 *
 * > <p>Blocks are lazily evaluated, so they are not rendered when being defined.
 */
trait BlocksManagementTrait
{
  /**
   * A map of block names => block contents.
   * > This must be public to be accessible for databiding.
   *
   * @var Component[][]
   */
  public $blocks = [];

  /**
   * @param string|Component[] $content
   * @return Component[]
   */
  static private function normalizeContent ($content)
  {
    if (is_string ($content))
      return $content === '' ? [] : [Text::from (null, $content)];
    else if (!is_array ($content))
      throw new \InvalidArgumentException("Block content must be <kbd>string|Component[]</kbd>");
    return $content;
  }

  /**
   * Appends content to a specific block.
   *
   * @param string             $name An arbitrary block name.
   * @param string|Component[] $content
   */
  function appendToBlock ($name, $content)
  {
    $content = self::normalizeContent ($content);
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else array_mergeInto ($this->blocks[$name], $content);
  }

  /**
   * Returns the content of a specific block.
   *
   * @param string $name An arbitrary block name.
   * @returns Component[]
   */
  function getBlock ($name)
  {
    return get ($this->blocks, $name, []);
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

  /**
   * Prepends content to a specific block.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function prependToBlock ($name, $content)
  {
    $content = self::normalizeContent ($content);
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] = array_merge ($content, $this->blocks[$name]);
  }

  /**
   * Saves a string on a specific block, overriding the previous content of it.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function setBlock ($name, $content)
  {
    $content             = self::normalizeContent ($content);
    $this->blocks[$name] = $content;
  }
}
