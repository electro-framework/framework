<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Interfaces\RenderableInterface;
use Selenia\Matisse\Debug\ComponentInspector;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Lib\DataBinder;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\AbstractProperties;
use Selenia\Matisse\Traits\Component\DataBindingTrait;
use Selenia\Matisse\Traits\Component\DOMNodeTrait;
use Selenia\Matisse\Traits\Component\MarkupBuilderTrait;

/**
 * The base class from which all components derive.
 */
abstract class Component implements RenderableInterface
{
  use MarkupBuilderTrait, DataBindingTrait, DOMNodeTrait;

  const ERR_NO_CONTEXT = "<h4>Rendering context not set</h4>The component was not initialized correctly.";
  /**
   * When true, data-binding resolution on the component's view is unaffected by data from the shared document view
   * model (which is set on {@see Context}); only the component's own view model is used.
   *
   * @var bool
   */
  const isolatedViewModel = false;
  /**
   * When true, the component's properties will be set on its data binder, so that data binding expressions can access
   * them via the `@`property syntax.
   *
   * @var bool;
   */
  const publishProperties = false;
  /**
   * @var string
   */
  static protected $propertiesClass;
  /**
   * An array containing the instance creation counters for each component class name.
   *
   * @var array
   */
  static protected $uniqueIDs = [];
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
  public $hidden = false;
  /**
   * The component's published properties (the ones which are settable through html attribute declarations on the source
   * markup). This property contains an object of class ComponentAttributes or of a subclass of it, depending on the
   * component class of the instance.
   *
   * @var AbstractProperties
   */
  public $props;
  /**
   * Indicates if the component supports a properties object.
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
   * How many times has this instance been rendered.
   *
   * <p>It's useful for determining if the component is being repeated, for instance.
   *
   * @var int
   */
  protected $renderCount = 0;
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
   * Creates a new component instance
   *
   * > <p>It is recommended to create component instances via {@see Component::create()} to correctly initialize them.
   */
  function __construct ()
  {
    $class                    = get_class ($this);
    $s                        = explode ('\\', $class);
    $this->className          = end ($s);
    $this->supportsProperties = isset($class::$propertiesClass);
    if ($this->supportsProperties) {
      $propClass   = $class::$propertiesClass;
      $this->props = new $propClass ($this);
    }
  }

  /**
   * Creates and renders a component inline.
   *
   * @param Component  $parent
   * @param array|null $props
   * @param array|null $bindings
   * @return string The rendered output.
   */
  static function _ (Component $parent, array $props = null, array $bindings = null)
  {
    return static::create ($parent, $props, $bindings)->getRendering ();
  }

  /**
   * Creates a component instance of the static class where this method was invoked on.
   *
   * > This method does not support components that require constructor injection.
   *
   * @param Component  $parent   The component's container component.
   * @param string[]   $props    A map of property names to property values.
   *                             Properties specified via this argument come only from markup attributes, not
   *                             from subtags.
   * @param array|null $bindings A map of attribute names and corresponding databinding expressions.
   * @return Component Component instance.
   * @throws ComponentException
   */
  static function create (Component $parent, array $props = null, array $bindings = null)
  {
    return (new static)->setup ($parent, $parent->context, $props, $bindings);
  }

  /**
   * Renders a set of components.
   *
   * @param Component[] $components The set of components to be rendered.
   */
  static function getRenderingOfSet (array $components = null)
  {
    ob_start (null, 0);
    if (isset($components))
      foreach ($components as $component)
        $component->run ();
    return ob_get_clean ();
  }

  /**
   * Renders a set of components.
   *
   * @param Component[] $components The set of components to be rendered.
   */
  static function renderSet (array $components = null)
  {
    if (isset($components))
      foreach ($components as $component)
        $component->run ();
  }

  static function throwUnknownComponent (Context $context, $tagName, Component $parent, $filename = null)
  {
    $paths    = implode ('', map ($context->macrosDirectories,
      function ($dir) { return "<li><path>$dir</path></li>"; }
    ));
    $filename = $filename ? "<kbd>$filename</kbd>" : "it";
    throw new ComponentException (null,
      "<h3>Unknown component / macro: <b>$tagName</b></h3>
<p>Neither a <b>class</b>, nor a <b>property</b>, nor a <b>macro</b> with the specified name were found.
<p>If it's a component, perhaps you forgot to register the tag...
<p>If it's a macro, Matisse is searching for $filename on these paths:<ul>$paths</ul>
<table>
  <th>Container component:<td><b>&lt;{$parent->getTagName()}></b>, of type <b>{$parent->className}</b>
</table>
");
  }

  function __debugInfo ()
  {
    $props = object_publicProps ($this);
    unset ($props['parent']);
    unset ($props['context']);
    unset ($props['children']);
    return $props;
  }

  function __get ($name)
  {
    throw new ComponentException($this, "Can't read from non existing property <b>$name</b>.");
  }

  function __set ($name, $value)
  {
    throw new ComponentException($this, $this->props
      ? "Can't set non-existing (or non-accessible) property <b>$name</b>."
      : "Can't set properties on a component that doesn't support them."
    );
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
  function attachAndRender (Component $c)
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
  function attachAndRenderSet (array $components)
  {
    $this->attach ($components);
    foreach ($components as $c)
      $c->run ();
  }

  /**
   * Renders a set of components as if they are children of this component.
   *
   * <p>**Warning:** the components will **not** br detached after begin rendered.
   *
   * @param Component[] $components A set of external, non-attached, components.
   * @return string
   */
  function attachSetAndGetContent (array $components)
  {
    ob_start (null, 0);
    $this->attachAndRenderSet ($components);
    return ob_get_clean ();
  }

  /**
   * Renders all children and returns the resulting markup.
   * ><p>**Note:** the component itself is not rendered.
   *
   * @return string
   */
  function getContent ()
  {
    ob_start (null, 0);
    $this->renderContent ();
    return ob_get_clean ();
  }

  function getContextClass ()
  {
    return Context::class;
  }

  /**
   * Gets the component's data binder.
   *
   * @return DataBinder
   */
  function getDataBinder ()
  {
    return $this->dataBinder;
  }

  function getRendering ()
  {
    ob_start (null, 0);
    $this->run ();
    return ob_get_clean ();
  }

  /**
   * Returns name of the tag that represents the component.
   * If the name is not set then it generates it from the class name and caches it.
   *
   * @return string
   */
  function getTagName ()
  {
    if (isset($this->tagName))
      return $this->tagName;
    preg_match_all ('#[A-Z][a-z]*#', $this->className, $matches, PREG_PATTERN_ORDER);

    return $this->tagName = ucfirst (strtolower (implode ('', $matches[0])));
  }

  /**
   * Sets the name of the tag that represents the component.
   * This is usually done by the parser, to increase the performance of getTagName().
   *
   * @param string $name
   */
  function setTagName ($name)
  {
    $this->tagName = $name;
  }

  /**
   * @return bool True if the component has any children at all.
   */
  function hasChildren ()
  {
    return !empty($this->children);
  }

  function inspect ($deep = false)
  {
    return ComponentInspector::inspect ($this, $deep);
  }

  /**
   * Indicates if either a constant value or a databinding expression were specified for the given attribute.
   *
   * @param string $fieldName
   * @return boolean
   */
  function isAttributeSet ($fieldName)
  {
    return isset($this->props->$fieldName) || $this->isBound ($fieldName);
  }

  /**
   * Called after the component has been created by the parsing process
   * and all attributes and children have also been parsed.
   * Override this to implement parsing-time behavior.
   */
  function onParsingComplete ()
  {
    //implementation is specific to each component type.
  }

  /**
   * Invokes doRender() recursively on the component's children (or a subset of).
   *
   * @param string|null $attrName [optional] An attribute name. If none, it renders all the component's children.
   *
   * @see renderContent()
   */
  function renderChildren ($attrName = null)
  {
    $children = isset($attrName) ? $this->getChildren ($attrName) : $this->children;
    foreach ($children as $child)
      $child->run ();
  }

  /**
   * Renders all children.
   * ><p>**Note:** the component itself is not rendered.<br><br>
   * ><p>**Note:** you should use this instead of {@see renderChildren()} when the full rendering of the component is
   * performed by its children. If the component does some rendering itself and additionally renders its children, call
   * {@see renderChildren()} from inside the component's rendering code.
   */
  function renderContent ()
  {
    if (!$this->context)
      throw new ComponentException($this, self::ERR_NO_CONTEXT);
    if ($this->isVisible ()) {
      $this->databind ();
      $this->preRender ();
      $this->renderChildren ();
      $this->postRender ();
    }
  }

  /**
   * Renders the component and any relevant children.
   *
   * Do not override! Use event handlers or override render() or renderChildren().
   * This method is called from run() or from renderChildren().
   */
  function run ()
  {
    if (!$this->context)
      throw new ComponentException($this, self::ERR_NO_CONTEXT);
    ++$this->renderCount;
    if ($this->isVisible ()) {
      $this->setupInheritedViewModel ();
      $this->databind ();       // This is done on the data binding context of the component's parent.
      $this->createView ();
      $this->setupViewModel (); // Here, the component may setup its view model and data binder.
      $this->setupView ();
      $this->preRender ();
      $this->render ();
      $this->postRender ();
      $this->afterRender ();
    }
  }

  function setContext ($context)
  {
    $this->context = $context;
  }

  /**
   * Sets the component's data binder.
   *
   * @param DataBinderInterface $binder
   * @return void
   */
  function setDataBinder (DataBinderInterface $binder)
  {
    $this->dataBinder = $binder;
  }

  /**
   * Initializes a newly created component by applying to it the applicable registered presets, followed by the given
   * properties, if any.
   *
   * > **Warning:** for some components this method will not be called (ex: Literal).
   *
   * @param array|null $props A map of the component's properties.
   * @throws ComponentException
   */
  function setProps (array $props = null)
  {
    if ($this->supportsProperties) {
      // Apply presets.

      if ($this->context)
        foreach ($this->context->presets as $preset)
          if (method_exists ($preset, $this->className))
            $preset->{$this->className} ($this, $props);

      // Apply properties.
      if ($props)
        $this->props->apply ($props);
    }
    else if ($props)
      throw new ComponentException($this, 'This component does not support properties.');
  }

  /**
   * Initializes a component right after instantiation.
   *
   * <p>**Note:** this method may not be called on some circumstances, for ex, if the component is rendered from a
   * middleware stack.
   *
   * @param Component|null                $parent   The component's container component (if any).
   * @param Context                       $context  A rendering context.
   * @param array|AbstractProperties|null $props    A map of property names to property values.
   *                                                Properties specified via this argument come only from markup
   *                                                attributes, not from subtags.
   * @param array|null                    $bindings A map of attribute names and corresponding databinding
   *                                                expressions.
   * @return Component Component instance.
   * @throws ComponentException
   */
  function setup (Component $parent = null, Context $context, $props = null, array $bindings = null)
  {
    if (is_object ($props)) {
      $this->props = $props;
      $props       = [];
    }
    $this->setContext ($context);
    $this->bindings = $bindings;
    $this->onCreate ($props, $parent);
    $this->setProps ($props);
    $this->init ();
    return $this;
  }

  /**
   * Called after the component is fully rendered.
   *
   * <p>Override to add debug logging, for instance.
   */
  protected function afterRender ()
  {
    //override
  }

  /**
   * Generates and/or initializes the component's view.
   *
   * <p>This is only relevant for {@see CompositeComponent} subclasses.
   */
  protected function createView ()
  {
    // override
  }

  protected function getUniqueId ()
  {
    if (array_key_exists ($this->className, self::$uniqueIDs))
      return ++self::$uniqueIDs[$this->className];
    self::$uniqueIDs[$this->className] = 1;

    return 1;
  }

  /**
   * Allows a component to perform additional initialization after being created. At that stage, the containing
   * document (or document fragment) rendering has not yet began.
   *
   * <p>**Note:** you **SHOULD** call the parent method when overriding this.
   *
   * > <p>**Tip:** override this method on a component subclass to set its script and stylesheet dependencies, so that
   * they are set before the page begins rendering.
   */
  protected function init ()
  {
    if ($this->autoId)
      $this->setAutoId ();
  }

  /**
   * @return bool Returns false if the component's rendering is disabled via the `hidden` property.
   */
  protected function isVisible ()
  {
    return !$this->hidden && (!isset($this->props) || !isset($this->props->hidden) || !$this->props->hidden);
  }

  /**
   * Allows a component to do something before it is initialized.
   * <p>The provided arguments have an informational purpose.
   *
   * @param array|null     $props
   * @param Component|null $parent
   */
  protected function onCreate (array $props = null, Component $parent = null)
  {
    //noop
  }

  /**
   * Do something after the component renders (ex. prepend to the output).
   */
  protected function postRender ()
  {
    //noop
  }

  /**
   * Do something before the component renders (ex. append to the output).
   */
  protected function preRender ()
  {
    //noop
  }

  /**
   * Implements the component's visual rendering code.
   * Implementation code should also call render() for each of the component's children, if any.
   *
   * <p>**DO NOT CALL DIRECTLY!**
   * <p>Use {@see run()} instead.
   *
   * > **Note:** this returns nothing; the output is sent directly to the output buffer.
   */
  protected function render ()
  {
    //implementation is specific to each component type.
  }

  /**
   * Do not call this. Set {@see autoId} instead.
   *
   * @return int New component ID.
   */
  protected function setAutoId ()
  {
    if ($this->regenerateId || (isset($this->props) && !property ($this->props, 'id'))) {
      $this->regenerateId = true; // if the component is re-rendered, always generate an id from now on.
      // Strip non alpha-numeric chars from generated name.
      $this->props->id =
        preg_replace ('/\W/', '', property ($this->props, 'name') ?: lcfirst ($this->className)) .
        $this->getUniqueId ();
    }

    return $this->props->id;
  }

  protected function setupInheritedViewModel ()
  {
    if (!$this->dataBinder && $this->parent)
      $this->dataBinder = $this->parent->dataBinder;
  }

  /**
   * Allows a component to perform additional view-related initialization, before it is rendered.
   *
   * <p>This is specially relevant for {@see CompositeComponent} subclasses.
   * For them, this provides an opportunity to access to the compiled view generated by the parsing process.
   *
   * <p>Override to add extra initialization.
   *
   * ><p>On a {@see CompositeComponent} subclass, you may use {@see view} to access the view, or {@see skin} to
   * directly access the compiled view, if it's a Matisse view.
   */
  protected function setupView ()
  {
    // override
  }

  /**
   * Sets up the component's view model, right before the component is rendered.
   *
   * > <p>**Note:** The default view model is the component instance itself, but you can override this on the subclass'
   * `viewModel()`.
   */
  protected function setupViewModel ()
  {
    $this->viewModel ();

    if (isset($this->viewModel)) {
      if ($this->dataBinder)
        $this->dataBinder = $this->dataBinder->withViewModel ($this->viewModel);
      else $this->dataBinder = new DataBinder ($this->context, $this->viewModel,
        static::publishProperties ? $this->props : null);

      if (exists ($this->shareViewModelAs))
        $this->context->viewModel[$this->shareViewModelAs] = $this->viewModel;
    }
    else if (static::publishProperties) {
      inspect ("PUBLISH PROPS " . $this->getTagName ());
      if (!$this->dataBinder) {
        inspect ("SETUPVIEWMODEL WITH DATABINDER NOT SET FOR " . $this->getTagName ());
        return;
      }
      $this->dataBinder = $this->dataBinder->withProps ($this->props);
      inspect ($this->dataBinder);
    }

    if ($this->dataBinder)
      $this->dataBinder = $this->dataBinder->withIsolation (static::isolatedViewModel);
    else inspect ("SETUPVIEWMODEL WITH DATABINDER NOT SET FOR " . $this->getTagName ());
  }

  /**
   * Override to set data on the component's view model.
   *
   * Data will usually be set on the component instance itself.
   * <p>Ex:
   * > `$this->data = ...`
   */
  protected function viewModel ()
  {
    //override
  }

}
