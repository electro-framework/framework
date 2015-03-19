<?php
namespace impactwave\matisse;
use impactwave\matisse\components\Page;
use impactwave\matisse\exceptions\FileIOException;

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
    'repeater' => 'impactwave\matisse\components\Repeater',
    'template' => 'impactwave\matisse\components\Template',
    'test'     => 'impactwave\matisse\components\Test',
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

  //TODO: use cache
  public function loadTemplate ($filePath)
  {
    // Try to load from the include path.
    if ($markup = loadFile ($filePath))
      return $markup;
    // Then, try to load from each of the registered directories.
    foreach ($this->context->templateDirectories as $dir) {
      if ($markup = loadFile ("$dir/$filePath", false))
        return $markup;
    }
    throw new FileIOException ($filePath);
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
