<?php
namespace impactwave\matisse;
use impactwave\matisse\components\Page;
use impactwave\matisse\exceptions\FileIOException;

class MatisseEngine
{
  /**
   * The rendering context for the current request.
   * @var Context
   */
  public $context;

  function __construct ()
  {
    $this->context = new Context();
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
    if (!($markup = loadFile ($filePath)))
      throw new FileIOException ($filePath);
    return $this->parse ($filePath);
  }
}
