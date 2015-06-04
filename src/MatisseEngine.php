<?php
namespace Selene\Matisse;
use Selene\Matisse\Components\Page;

class MatisseEngine
{
  const MAX_BUFFER_SIZE = 1048576; // 1Mb = 1024 * 1024

  /**
   * A map of tag names to fully qualified PHP component class names.
   * It is initialized to the core Matisse components that can be instantiated via tags.
   * @var array string => string
   */
  private static $coreTags = [
    'Repeater' => 'Selene\Matisse\Components\Repeater',
    'Template' => 'Selene\Matisse\Components\Template',
    'If'       => 'Selene\Matisse\Components\If_',
    'Literal'  => 'Selene\Matisse\Components\Literal',
    'Apply'    => 'Selene\Matisse\Components\Apply',
  ];

  /**
   * @param           $markup
   * @param Context   $ctx The rendering context for the current request.
   * @param Component $parent
   * @param Page      $page
   * @return Component|Page
   * @throws Exceptions\ParseException
   */
  function parse ($markup, Context $ctx, Component $parent = null, Page $page = null)
  {
    if (!$page) $page = $parent ? $parent->page : new Page($ctx);
    if (!$parent) $parent = $page;
    if ($parent->page != $page)
      throw new \InvalidArgumentException ("Error on parse(): parent node belongs to a different page than the specified one.");
    $parser = new Parser($ctx);
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
   * Creates a new rendering context.
   *
   * You should this factory method whenever you need a new context, instead of creating it directly.
   *
   * @param array  $tags        A map of tag names to fully qualified PHP class names.
   * @param object $pipeHandler A value for {@see $pipeHandler}
   * @return Context
   */
  function createContext (array $tags, $pipeHandler = null)
  {
    $tags = array_merge (self::$coreTags, $tags);
    $ctx  = new Context($tags, $pipeHandler);
    return $ctx;
  }

}
