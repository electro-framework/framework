<?php
namespace Selenia\Matisse\Traits\Context;

/**
 * Manages block of markup content.
 */
trait BlocksManagementTrait
{
  /**
   * A map of block names => block contents.
   * > This must be public to be accessible for databiding.
   *
   * @var string[]
   */
  public $blocks = [];

  /**
   * Appends content to a specific block.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function appendToBlock ($name, $content)
  {
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] .= $content;
  }

  /**
   * Returns the content of a specific block.
   *
   * @param string $name An arbitrary block name.
   * @returns string
   */
  function getBlock ($name)
  {
    return get ($this->blocks, $name, '');
  }

  /**
   * Checks if a block with the specified name exists and has content.
   *
   * @param string $name An arbitrary block name.
   * @return bool
   */
  function hasBlock ($name)
  {
    return get ($this->blocks, $name, '') != '';
  }

  /**
   * Prepends content to a specific block.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function prependToBlock ($name, $content)
  {
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] = $content . $this->blocks[$name];
  }

  /**
   * Saves a string on a specific block, overriding the previous content of it.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function setBlock ($name, $content)
  {
    $this->blocks[$name] = $content;
  }
}
