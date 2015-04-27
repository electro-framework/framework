<?php
namespace selene\matisse;
use selene\matisse\components\Page;
use selene\matisse\exceptions\FileIOException;

class MatisseEngine
{
  const MAX_BUFFER_SIZE = 1048576; // 1Mb = 1024 * 1024

  /**
   * The rendering context for the current request.
   * @var Context
   */
  public $context;

  /**
   * A map of tag names to fully qualified PHP class names.
   * It is initialized to the core Matisse components that can be instantiated via tags.
   * @var array string => string
   */
  private $tags = [
    'repeater' => 'selene\matisse\components\Repeater',
    'template' => 'selene\matisse\components\Template',
    'test'     => 'selene\matisse\components\Test',
  ];

  function __construct ()
  {
    $this->reset ();
  }

  public function reset ()
  {
    $this->context = new Context($this->tags);
  }

  public function parse ($markup, Component $parent = null, Page $page = null)
  {
    if (!$page) $page = $parent ? $parent->page : new Page($this->context);
    if (!$parent) $parent = $page;
    if ($parent->page != $page)
      throw new \InvalidArgumentException ("Error on parse(): parent node belongs to a different page than the specified one.");
    $parser = new Parser($this->context);
    $parser->parse ($markup, $parent, $page);
    return $parent;
  }

  public function render (Component $root)
  {
    ob_start (null, self::MAX_BUFFER_SIZE);
    $root->run ();
    return ob_get_clean ();
  }

  public function registerComponents (array $map)
  {
    $this->tags = array_merge ($this->tags, $map);
  }
}
