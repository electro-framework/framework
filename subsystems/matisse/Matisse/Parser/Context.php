<?php
namespace Selenia\Matisse\Parser;

use Selenia\Matisse\Components\Internal\Page;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Exceptions\ParseException;

class Context
{
  /**
   * Remove white space around raw markup blocks.
   *
   * @var bool
   */
  public $condenseLiterals = false;
  /**
   * Set to true to generate pretty-printed markup.
   *
   * @var bool
   */
  public $debugMode = false;
  /**
   * @var string[]
   */
  public $macrosDirectories = [];
  /**
   * File extension of macro files.
   *
   * @var string
   */
  public $macrosExt = '.html';
  /**
   * A stack of presets.
   *
   * Each preset is an instance of a class where methods are named after tags or preset names.
   * When components are being instantiated, if they match a tag name or preset name on any of the stacked presets,
   * they will be passed to the corresponding methods on the presets to be transformed.
   *
   * @var array
   */
  public $presets = [];
  /**
   * A service locator function that receives a class/interface/service name and returns a new instance of it.
   *
   * @var callable
   */
  public $serviceLocator;
  /**
   * The view-model data for the current rendering context.
   *
   * @var array
   */
  public $viewModel = [];
  /**
   * A list of memorized macros for the current request.
   *
   * @var Macro[]
   */
  private $macros = [];
  /**
   * A class instance who's methods provide pipe implementations.
   *
   * The handler can be an instance of a proxy class, which dynamically resolves the pipe invocations trough a
   * `__call` method.
   *
   * > Pipe Handlers should throw an exception if a handler method is not found.
   *
   * > <p>An handler implementation is available on the {@see PipeHandler} class.
   *
   * @var object
   */
  private $pipeHandler;
  /**
   * A map of tag names to fully qualified PHP class names.
   *
   * @var array string => string
   */
  private $tags = [];

  /**
   * @param array  $tags        A map of tag names to fully qualified PHP class names.
   * @param object $pipeHandler A value for {@see $pipeHandler}
   */
  function __construct (array &$tags, $pipeHandler = null)
  {
    $this->tags        =& $tags;
    $this->pipeHandler = $pipeHandler;
  }

  function addMacro ($name, Macro $macro)
  {
    if (isset($this->macros[$name]))
      throw new ParseException("Can't redefine the <kbd>$name</kbd> macro");
    $this->macros[$name] = $macro;
    // Remove macro from its original location. It now lives on only as a detached template.
    $macro->remove ();
  }

  function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
  }

  /**
   * @param string $name
   * @return Macro
   */
  function getMacro ($name)
  {
    return get ($this->macros, $name);
  }

  /**
   * @param $name
   * @return callable A function that implements the pipe.
   *                  <p>Note: the function may throw an {@see HandlerNotFoundException} if it can't handle
   *                  the required pipe.
   */
  function getPipe ($name)
  {
    if (!isset($this->pipeHandler))
      throw new \RuntimeException ("Can't use pipes if no pipe handler is set.");
    return [$this->pipeHandler, $name];
  }

  /**
   * Searches for a file defining a macro for the given tag name.
   *
   * @param string $tagName
   * @param Page   $root
   * @return Macro
   * @throws FileIOException
   * @throws ParseException
   */
  function loadMacro ($tagName, Page $root)
  {
    $filename = normalizeTagName ($tagName) . $this->macrosExt;
    $content  = $this->loadMacroFile ($filename);
    $parser   = new Parser($this);
    $parser->parse ($content, $root);
    $macro = $this->getMacro ($tagName);
    if (isset($macro))
      return $macro;
    throw new ParseException("File <b>$filename</b> does not define a macro named <b>$tagName</b>.");
  }

  private function loadMacroFile ($filename)
  {
    foreach ($this->macrosDirectories as $dir) {
      $f = loadFile ("$dir/$filename", false);
      if ($f) return $f;
    }
    throw new FileIOException($filename);
  }

}
