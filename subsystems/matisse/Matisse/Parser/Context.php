<?php
namespace Selenia\Matisse\Parser;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Components;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\GenericHtmlComponent;
use Selenia\Matisse\Components\Macro\MacroCall;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Lib\AssetsContext;
use Selenia\Matisse\Traits\Context\AssetsManagementTrait;
use Selenia\Matisse\Traits\Context\BlocksManagementTrait;
use Selenia\Matisse\Traits\Context\MacrosManagementTrait;

/**
 * A Matisse rendering context.
 *
 * <p>It is shared between all components on a document.
 */
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
    'Apply'                      => Components\Apply::class,
    'AssetsGroup'                => Components\AssetsGroup::class,
    'Content'                    => Components\Content::class,
    'If'                         => Components\If_::class,
    'Include'                    => Components\Include_::class,
    Components\Literal::TAG_NAME => Components\Literal::class,
    'Macro'                      => Components\Macro\Macro::class,
    'MacroParam'                 => Components\Macro\MacroParam::class,
    'Script'                     => Components\Script::class,
    'Style'                      => Components\Style::class,
    'Repeat'                     => Components\Repeat::class,
    MacroCall::TAG_NAME          => MacroCall::class,
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
   * Callbacks also receive a nullable array argument with the properties being applied.
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
   * The view service that instantiated the current rendering engine and its associated rendering context (this
   * instance).
   *
   * @var ViewServiceInterface|null
   */
  public $viewService;
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
    $this->tags   = self::$coreTags;
    $this->assets = $this->mainAssets = new AssetsContext;
  }

  /**
   * Creates a component corresponding to the specified tag and optionally sets its attributes.
   *
   * <p>This is called by the parser.
   *
   * @param string     $tagName
   * @param Component  $parent   The component's container component.
   * @param string[]   $props    A map of property names to property values.
   *                             Properties specified via this argument come only from markup attributes, not
   *                             from subtags.
   * @param array|null $bindings A map of attribute names and corresponding databinding expressions.
   * @param bool       $generic  If true, an instance of GenericComponent is created.
   * @param boolean    $strict   If true, failure to find a component class will throw an exception.
   *                             If false, an attempt is made to load a macro with the same name,
   * @return Component Component instance. For macros, an instance of Macro is returned.
   * @throws ComponentException
   */
  function createComponentFromTag ($tagName, Component $parent, array $props = null, array $bindings = null,
                                   $generic = false, $strict = false)
  {
    if ($generic) {
      $component = new GenericHtmlComponent($tagName, $props);
      return $component;
    }
    $class = $this->getClassForTag ($tagName);
    if (!$class) {
      if ($strict)
        Component::throwUnknownComponent ($this, $tagName, $parent);

      // Component class not found.
      // Convert the tag to a MacroInstance component instance that will attempt to load a macro with the same
      // name as the tag name.

      if (is_null ($props))
        $props = [];
      $props['macro'] = $tagName;
      $component      = new MacroCall;
    }

    // Component class was found.

    else $component = $this->injector->make ($class);

    // For both types of components:

    $component->setTagName ($tagName);
    return $component->setup ($parent, $this, $props, $bindings);
  }

  function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
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
   * @return object
   */
  function getPipeHandler ()
  {
    return $this->pipeHandler;
  }

  function setPipeHandler ($pipeHandler)
  {
    $this->pipeHandler = $pipeHandler;
  }

  function registerTags (array $tags)
  {
    $this->tags = array_merge ($this->tags, $tags);
  }

}
