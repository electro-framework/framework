<?php
namespace Selenia\Matisse\Parser;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Lib\AssetsContext;
use Selenia\Matisse\Traits\Context\AssetsAPITrait;
use Selenia\Matisse\Traits\Context\BlocksAPITrait;
use Selenia\Matisse\Traits\Context\ComponentsAPITrait;
use Selenia\Matisse\Traits\Context\MacrosAPITrait;
use Selenia\Matisse\Traits\Context\PipesAPITrait;

/**
 * A Matisse rendering context.
 *
 * <p>The context holds state and configuration information shared between all components on a document.
 * It also provides APIs for accessing/managing Assets, Blocks and Macros.
 */
class Context
{
  use AssetsAPITrait;
  use BlocksAPITrait;
  use ComponentsAPITrait;
  use PipesAPITrait;
  use MacrosAPITrait;

  /**
   * A map of databinding expressions to compiled functions.
   *
   * @var array [string => Closure]
   */
  static $compiledExpressions = [];
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
   * Each preset is an instance of a class where methods are named with component class names.
   * When components are being instantiated, if they match a class name on any of the stacked presets,
   * they will be passed to the corresponding methods for additional initialization.
   * Callbacks also receive a nullable array argument with the properties being applied.
   *
   * @var array
   */
  public $presets = [];
  /**
   * The shared view-model data for the current rendering context.
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

  function __construct ()
  {
    $this->tags   = self::$coreTags;
    $this->assets = $this->mainAssets = new AssetsContext;
  }

}
