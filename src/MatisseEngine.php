<?php
namespace Selene\Matisse;
use Selene\Matisse\Components\Page;
use Selene\Matisse\Exceptions\FileIOException;

class MatisseEngine
{
  const MAX_BUFFER_SIZE = 1048576; // 1Mb = 1024 * 1024

  /**
   * The rendering context for the current request.
   * @var Context
   */
  public $context;

  /**
   * A map of tag names to fully qualified PHP component class names.
   * It is initialized to the core Matisse components that can be instantiated via tags.
   * @var array string => string
   */
  private $tags = [
    'repeater' => 'Selene\Matisse\Components\Repeater',
    'template' => 'Selene\Matisse\Components\Template',
    'test'     => 'Selene\Matisse\Components\Test',
  ];

  /**
   * Map of pipe names to pipe implementation functions.
   *
   * Pipes can be used on databinding expressions. Ex: {!a.c|myPipe}
   * @var array
   */
  private $pipes = [];

  function __construct ()
  {
    $this->reset ();
  }

  function reset ()
  {
    $this->context = new Context($this->tags, $this->pipes);
  }

  function parse ($markup, Component $parent = null, Page $page = null)
  {
    if (!$page) $page = $parent ? $parent->page : new Page($this->context);
    if (!$parent) $parent = $page;
    if ($parent->page != $page)
      throw new \InvalidArgumentException ("Error on parse(): parent node belongs to a different page than the specified one.");
    $parser = new Parser($this->context);
    $parser->parse ($markup, $parent, $page);
    return $parent;
  }

  /**
   * Renders the given component tree and returns the resulting markup.
   * @param Component $root The component tree's root element.
   * @return string The resulting markup (usually HTML).
   */
  function render (Component $root)
  {
    ob_start (null, self::MAX_BUFFER_SIZE);
    $root->run ();
    return ob_get_clean ();
  }

  /**
   * Registers a map of tag names to fully qualified PHP component class names.
   * @param array $map
   */
  function registerComponents (array $map)
  {
    $this->tags = array_merge ($this->tags, $map);
  }

  /**
   * Register a set of pipes for use on databinding expressions when rendering.
   * @param array|object $pipes Either a map of pipe names to pipe implementation functions or an instance of a class
   *                            where each public method (except the constructor) is a named pipe function.
   */
  function registerPipes ($pipes)
  {
    if (is_object ($pipes)) {
      $keys   = array_diff (get_class_methods ($pipes), ['__construct']);
      $values = array_map (function ($v) use ($pipes) { return [$pipes, $v]; }, $keys);
      $pipes  = array_combine ($keys, $values);
    };
    $this->pipes = array_merge ($this->pipes, $pipes);
  }
}
