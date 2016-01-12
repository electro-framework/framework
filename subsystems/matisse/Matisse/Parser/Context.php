<?php
namespace Selenia\Matisse\Parser;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Matisse\Components;
use Selenia\Matisse\Components\Macro\MacroInstance;
use Selenia\Matisse\Traits\Context\AssetsManagementTrait;
use Selenia\Matisse\Traits\Context\BlocksManagementTrait;
use Selenia\Matisse\Traits\Context\MacrosManagementTrait;

class Context
{
  use AssetsManagementTrait;
  use BlocksManagementTrait;
  use MacrosManagementTrait;

  /**
   * A map of databinding expressions to compiled functions.
   *
   * @var array [string => Closure]
   */
  static $expressions = [];
  /**
   * A map of tag names to fully qualified PHP component class names.
   * It is initialized to the core Matisse components that can be instantiated via tags.
   *
   * @var array string => string
   */
  private static $coreTags = [
    'Apply'   => Components\Apply::class,
    'Content' => Components\Content::class,
    'If'      => Components\If_::class,
    'Include' => Components\Include_::class,
    Components\Literal::TAG_NAME
              => Components\Literal::class,
    'Macro'   => Components\Macro\Macro::class,
    'Script'  => Components\Script::class,
    'Style'   => Components\Style::class,
    'Repeat'  => Components\Repeat::class,
    MacroInstance::TAG_NAME
              => MacroInstance::class,
  ];

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
   * The injector allows the creation of components with yet unknown dependencies.
   *
   * @var InjectorInterface
   */
  public $injector;
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
   * The view-model data for the current rendering context.
   * <p>This is usually shared throughout the whole document.
   *
   * @var array
   */
  public $viewModel = [];
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
  private $tags;

  function __construct ()
  {
    $this->tags = self::$coreTags;
  }

  function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
  }

  /**
   * Use this instead of the `clone` operator to get a correct clone of an instance of this class.
   * <p>Changes to assets on the cloned instance will affect the original instance.
   */
  function getClone ()
  {
    $clone                        = clone $this;
    $clone->stylesheets           =& $this->stylesheets;
    $clone->scripts               =& $this->scripts;
    $clone->inlineCssStyles       =& $this->inlineCssStyles;
    $clone->inlineDeferredScripts =& $this->inlineDeferredScripts;
    $clone->inlineScripts         =& $this->inlineScripts;
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

  function registerTags (array $tags)
  {
    $this->tags = array_merge ($this->tags, $tags);
  }

  function setPipeHandler ($pipeHandler)
  {
    $this->pipeHandler = $pipeHandler;
  }

}
