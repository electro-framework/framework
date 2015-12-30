<?php
namespace Selenia\Matisse\Parser;

use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\FileIOException;

class Context
{
  /**
   * Remove white space around raw markup blocks.
   * @var bool
   */
  public $condenseLiterals = false;
  /**
   * Set to true to generate pretty-printed markup.
   * @var bool
   */
  public $debugMode = false;
  /**
   * A service locator function that receives a class/interface/service name and returns a new instance of it.
   * @var callable
   */
  public $serviceLocator;
  /**
   * @var string[]
   */
  public $macrosDirectories = [];
  /**
   * File extension of macro files.
   * @var string
   */
  public $macrosExt = '.html';
  /**
   * A stack of presets.
   *
   * Each preset is an instance of a class where methods are named after tags or preset names.
   * When components are being instantiated, if they match a tag name or preset name on any of the stacked presets,
   * they will be passed to the corresponding methods on the presets to be transformed.
   * @var array
   */
  public $presets = [];
  /**
   * The view-model data for the current rendering context.
   * @var array
   */
  public $viewModel = [];
  /**
   * A list of memorized macros for the current request.
   * @var array
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
    $this->macros[$name] = $macro;
  }

  function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
  }

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

  function loadMacro ($filename)
  {
    foreach ($this->macrosDirectories as $dir) {
      $f = loadFile ("$dir/$filename", false);
      if ($f) return $f;
    }
    throw new FileIOException($filename);
  }
}
