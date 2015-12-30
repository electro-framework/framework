<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Components\Internal\Page;
use Selenia\Matisse\Components\Macro\MacroInstance;
use Selenia\Matisse\Debug\ComponentInspector;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Exceptions\ParseException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Parser;
use Selenia\Matisse\Properties\Base\AbstractProperties;
use Selenia\Matisse\Traits\DataBindingTrait;
use Selenia\Matisse\Traits\DOMNodeTrait;
use Selenia\Matisse\Traits\MarkupBuilderTrait;

/**
 * The base class from which all components derive.
 */
abstract class Component
{
  use MarkupBuilderTrait, DataBindingTrait, DOMNodeTrait;

  /**
   * @var string
   */
  protected static $propertiesClass;
  /**
   * An array containing the instance creation counters for each component class name.
   *
   * @var array
   */
  protected static $uniqueIDs = [];
  /**
   * The component's PHP class name.
   *
   * @var string
   */
  public $className;
  /**
   * The rendering context for the current request.
   *
   * @var Context
   */
  public $context;
  /**
   * When TRUE indicates that the component will not be rendered.
   *
   * @var boolean
   */
  public $inactive = false;
  /**
   * Points to the root of the components tree.
   *
   * @var Page
   */
  public $page;
  /**
   * The component's published properties (the ones which are settable through html attribute declarations on the source
   * markup). This property contains an object of class ComponentAttributes or of a subclass of it, depending on the
   * component class of the instance.
   *
   * @var AbstractProperties
   */
  public $props;
  /**
   * Indicates if the component supports the IAttributes interface.
   *
   * @var boolean
   */
  public $supportsProperties;
  /**
   * Set to true on a component class definition to automatically assign an ID to instances.
   *
   * @see setAutoId().
   * @var bool
   */
  protected $autoId = false;
  /**
   * When true, forces generation of a new auto-id, event if the component already has an assigned id.
   *
   * @var bool
   */
  private $regenerateId = false;
  /**
   * Cache for getTagName()
   *
   * @var string
   */
  private $tagName;

  /**
   * Creates a new component instance and optionally sets its attributes and styles.
   *
   * @param array   $properties A map of the component's properties.
   * @param Context $context    The rendering context for the current request.
   * @throws ComponentException
   */
  public function __construct (Context $context, array $properties = null)
  {
    $class                    = get_class ($this);
    $s                        = explode ('\\', $class);
    $this->context            = $context;
    $this->className          = end ($s);
    $this->supportsProperties = isset($class::$propertiesClass);
    if ($this->supportsProperties) {
      $propClass   = $class::$propertiesClass;
      $this->props = new $propClass ($this);

      // Apply presets.
      foreach ($context->presets as $preset)
        if (method_exists ($preset, $this->className))
          $preset->{$this->className} ($this);

      // Apply properties.
      if ($properties)
        $this->props->apply ($properties);
    }
    else if ($properties)
      throw new ComponentException($this, 'This component does not support properties.');
  }

  /**
   * Creates a component corresponding to the specified tag and optionally sets its attributes.
   *
   * @param Context   $context
   * @param Component $parent  This is used only for error reporting. You should still manually add the component to
   *                           it's parent's child list or source parameter.
   * @param string    $tagName
   * @param array     $attributes
   * @param bool      $generic If true, an instance of GenericComponent is created.
   * @param boolean   $strict  If true, failure to find a component class will throw an exception.
   *                           If false, an attempt is made to load a macro with the same name,
   * @return Component Component instance. For macros, an instance of Macro is returned.
   * @throws ComponentException
   * @throws ParseException
   */
  static public function create (Context $context, Component $parent, $tagName, array $attributes = null,
                                 $generic = false, $strict = false)
  {
    if ($generic) {
      $component = new GenericHtmlComponent($context, $tagName, $attributes);

      return $component;
    }
    $class = $context->getClassForTag ($tagName);
    if (!$class) {
      if ($strict)
        self::throwUnknownComponent ($context, $tagName, $parent);

      // Component class not found.
      // Try to load a macro with the same tag name.

      $macro = $context->getMacro ($tagName);
      try {
        if (is_null ($macro))
          $macro = self::loadMacro ($context, $parent, $tagName);
      }
      catch (FileIOException $e) {
        self::throwUnknownComponent ($context, $tagName, $parent);
      }
      $component = new MacroInstance($context, $tagName, $macro, $attributes);
    }

    // Component class was found.

    else $component = new $class ($context, $attributes);

    // For both types of components:

    $component->setTagName ($tagName);

    return $component;
  }

  static function loadMacro (Context $context, Component $parent, $tagName)
  {
    $filename = normalizeTagName ($tagName) . $context->macrosExt;
    $content  = $context->loadMacro ($filename);
    $parser   = new Parser($context);
    $parser->parse ($content, $parent);
    $macro = $context->getMacro ($tagName);
    if (isset($macro)) {
      $macro->remove ();

      return $macro;
    }
    throw new ParseException("File <b>$filename</b> does not define a macro named <b>$tagName</b>.");
  }

  /**
   * Renders a set of components.
   *
   * @param Component[] $components The set of components to be rendered.
   */
  protected static function renderSet (array $components = null)
  {
    if (isset($components))
      foreach ($components as $component)
        $component->run ();
  }

  private static function throwUnknownComponent (Context $context, $tagName, Component $parent)
  {
    $paths = implode ('', map ($context->macrosDirectories,
      function ($dir) { return "<li><path>$dir</path></li>"; }));
    throw new ComponentException (null,
      "<h3>Unknown tag: <b>&lt;$tagName></b></h3>
<p>Neither a <b>class</b>, nor a <b>parameter</b>, nor a <b>macro</b> implementing that tag were found.
<p>Perhaps you forgot to register the tag?
<p>If it's a macro, Matisse is searching for it on these paths:<ul>$paths</ul>
<table>
  <th>Container component:<td><b>&lt;{$parent->getTagName()}></b>, of type <b>{$parent->className}</b>
</table>
");
  }

  public function __get ($name)
  {
    throw new ComponentException($this, "Can't read from non existing property <b>$name</b>.");
  }

  public function __set ($name, $value)
  {
    throw new ComponentException($this, "Can't set non-existing (or non-accessible) property <b>$name</b>.");
  }

  function __toString ()
  {
    try {
      return $this->inspect ();
    }
    catch (\Exception $e) {
      inspect ($e->getTraceAsString ());
      return '';
    }
  }

  /**
   * Runs a private child component that does not belong to the hierarchy.
   *
   * <p>**Warning:** the component will **not** be detached after begin rendered.
   *
   * @param Component $c
   */
  public final function attachAndRender (Component $c)
  {
    $this->attach ($c);
    $c->run ();
  }

  /**
   * Renders a set of components as if they are children of this component.
   *
   * <p>**Warning:** the components will **not** br detached after begin rendered.
   *
   * @param Component[] $components A set of external, non-attached, components.
   */
  public function attachAndRenderSet (array $components)
  {
    $this->attach ($components);
    foreach ($components as $c)
      $c->run ();
  }

  /**
   * Renders all children and returns the resulting markup.
   * ><p>**Note:** the component itself is not rendered.
   *
   * @return string
   */
  public function getContent ()
  {
    ob_start (null, 0);
    $this->renderContent ();
    return ob_get_clean ();
  }

  /**
   * Returns name of the tag that represents the component.
   * If the name is not set then it generates it from the class name and caches it.
   *
   * @return string
   */
  public final function getTagName ()
  {
    if (isset($this->tagName))
      return $this->tagName;
    preg_match_all ('#[A-Z][a-z]*#', $this->className, $matches, PREG_PATTERN_ORDER);

    return $this->tagName = ucfirst (strtolower (implode ('-', $matches[0])));
  }

  /**
   * Sets the name of the tag that represents the component.
   * This is usually done by the parser, to increase the performance of getTagName().
   *
   * @param string $name
   */
  public final function setTagName ($name)
  {
    $this->tagName = $name;
  }

  /**
   * @return bool True if the component has any children at all.
   */
  public function hasChildren ()
  {
    return !empty($this->children);
  }

  function inspect ($deep = true)
  {
    return ComponentInspector::inspect ($this, $deep);
  }

  /**
   * Indicates if either a constant value or a databinding expression were specified for the given attribute.
   *
   * @param string $fieldName
   * @return boolean
   */
  public final function isAttributeSet ($fieldName)
  {
    return isset($this->props->$fieldName) || $this->isBound ($fieldName);
  }

  /**
   * Called after the component has been created by the parsing process
   * and all attributes and children have also been parsed.
   * Override this to implement parsing-time behavior.
   */
  public function onParsingComplete ()
  {
    //implementation is specific to each component type.
  }

  /**
   * Invokes doRender() recursively on the component's children (or a subset of).
   *
   * @param string|null $attrName [optional] An attribute name. If none, it renders all the component's children.
   */
  public function renderChildren ($attrName = null)
  {
    $children = isset($attrName) ? $this->getChildren ($attrName) : $this->children;
    foreach ($children as $child)
      $child->run ();
  }

  /**
   * Renders all children.
   * ><p>**Note:** the component itself is not rendered.
   */
  public function renderContent ()
  {
    if (!$this->inactive) {
      $this->databind ();
      $this->preRender ();
      $this->renderChildren ();
      $this->postRender ();
    }
  }

  /**
   * Executes the component and any relevant children.
   *
   * Do not override! Use event handlers or override render() or renderChildren().
   * This method is called from run() or from renderChildren().
   */
  public final function run ()
  {
    if (!$this->inactive) {
      $this->databind ();
      if (!isset($this->props) || !isset($this->props->hidden) || !$this->props->hidden) {
        $this->preRender ();
        $this->render ();
        $this->postRender ();
      }
    }
  }

  protected function getUniqueId ()
  {
    if (array_key_exists ($this->className, self::$uniqueIDs))
      return ++self::$uniqueIDs[$this->className];
    self::$uniqueIDs[$this->className] = 1;

    return 1;
  }

  protected function postRender ()
  {
    //stub
  }

  protected function preRender ()
  {
    //stub
  }

  /**
   * Implements the component's visual rendering code.
   * Implementation code should also call render() for each of the
   * component's children, if any.
   * DO NOT CALL DIRECTLY FROM YOUR COMPONENT!
   *
   * @see run()
   */
  protected function render ()
  {
    //implementation is specific to each component type.
  }

  protected function setAutoId ()
  {
    if ($this->regenerateId || (isset($this->props) && !isset($this->props->id))) {
      $this->regenerateId = true; // if the component is re-rendered, always generate an id from now on.
      // Strip non alpha-numeric chars from generated name.
      $this->props->id =
        preg_replace ('/\W/', '', property ($this->props, 'name', strtolower ($this->className))) .
        $this->getUniqueId ();
    }

    return $this->props->id;
  }

}
