<?php
namespace Selenia\Matisse;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Base\GenericComponent;
use Selenia\Matisse\Components\Page;
use Selenia\Matisse\Components\TemplateInstance;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Exceptions\ParseException;
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
   * Indicates if the component supports the IAttributes interface.
   *
   * @var boolean
   */
  public $supportsAttributes;
  /**
   * The component's published properties (the ones which are settable through xml attribute declarations on the source
   * markup). This property contains an object of class ComponentAttributes or of a subclass of it, depending on the
   * component class of the instance. Do not access this property directly. @see Component::attrs()
   *
   * @var ComponentAttributes
   */
  protected $attrsObj;
  /**
   * Set to true on a component class definition to automatically assign an ID to instances.
   *
   * @see setAutoId().
   * @var bool
   */
  protected $autoId = false;
  /**
   * The rendering context for the current request.
   * @var Context
   */
  public $context;
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
   * @param array   $attributes A map of the component's attributes.
   * @param Context $context    The rendering context for the current request.
   * @throws ComponentException
   */
  public function __construct (Context $context, array $attributes = null)
  {
    $class                    = get_class ($this);
    $s                        = explode ('\\', $class);
    $this->context            = $context;
    $this->className          = end ($s);
    $this->supportsAttributes = $this instanceof IAttributes;
    if ($this->supportsAttributes) {
      $this->attrsObj = $this->newAttributes ();

      // Apply presets.
      foreach ($context->presets as $preset)
        if (method_exists ($preset, $this->className))
          $preset->{$this->className} ($this);

      // Apply attributes.
      if ($attributes)
        foreach ($attributes as $name => $value)
          $this->attrsObj->set ($name, $value);
    }
    else if ($attributes)
      throw new ComponentException($this, 'This component does not support attributes.');
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
   *                           If false, an attempt is made to load a template with the same name,
   * @return Component Component instance. For templates, an instance of Template is returned.
   *                           You should them
   * @throws ComponentException
   * @throws ParseException
   */
  static public function create (Context $context, Component $parent, $tagName, array $attributes = null,
                                 $generic = false, $strict = false)
  {
    if ($generic) {
      $component = new GenericComponent($context, $tagName, $attributes);

      return $component;
    }
    $class = $context->getClassForTag ($tagName);
    if (!$class) {
      if ($strict)
        self::throwUnknownComponent ($context, $tagName, $parent);

      // Component class not found.
      // Try to load a template with the same tag name.

      $template = $context->getTemplate ($tagName);
      try {
        if (is_null ($template))
          $template = self::loadTemplate ($context, $parent, $tagName);
      } catch (FileIOException $e) {
        self::throwUnknownComponent ($context, $tagName, $parent);
      }
      $component = new TemplateInstance($context, $tagName, $template, $attributes);
    }

    // Component class was found.

    else $component = new $class ($context, $attributes);

    // For both types of components:

    $component->setTagName ($tagName);

    return $component;
  }

  static function loadTemplate (Context $context, Component $parent, $tagName)
  {
    $filename = normalizeTagName ($tagName) . $context->templatesExt;
    $content  = $context->loadTemplate ($filename);
    $parser   = new Parser($context);
    $parser->parse ($content, $parent);
    $template = $context->getTemplate ($tagName);
    if (isset($template)) {
      $template->remove ();

      return $template;
    }
    throw new ParseException("File <b>$filename</b> does not define a template named <b>$tagName</b>.");
  }

  /**
   * Gets the name of the class.
   * @return string
   */
  public static function ref ()
  {
    return get_called_class ();
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
        $component->doRender ();
  }

  protected static function runSet (array $components = null)
  {
    self::renderSet ($components);
  }

  private static function throwUnknownComponent (Context $context, $tagName, Component $parent)
  {
    $paths = implode ('', map ($context->templateDirectories,
      function ($dir) { return "<li><path>$dir</path></li>"; }));
    throw new ComponentException (null,
      "<h3>Unknown tag: <b>&lt;$tagName></b></h3>
<p>Neither a class, parameter or template implementing that tag were found.
<p>Perhaps you forgot to register the tag?
<p>If it's a template, Matisse is searching for it on these paths:<ul>$paths</ul>
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
    throw new ComponentException($this, "Can't set non existing property <b>$name</b>.");
  }

  function __toString ()
  {
    return $this->inspect ();
  }

  /**
   * Performs both the component's rendering and its children's.
   * Do not override! Use event handlers or override render() or renderChildren().
   * This method is called from run() or from renderChildren().
   */
  public final function doRender ()
  {
    if (!$this->inactive) {
      $this->databind ();
      if (!isset($this->attrsObj) || !isset($this->attrsObj->hidden) || !$this->attrsObj->hidden) {
        $this->preRender ();
        $this->render ();
        $this->postRender ();
      }
    }
  }

  /**
   * Escapes (secures) data for output.<br>
   *
   * <p>Array attribute values are converted to space-separated value string lists.
   * > A useful use case for an array attribute is the `class` attribute.
   *
   * Object attribute values generate either:
   * - a space-separated list of keys who's corresponding value is truthy;
   * - a semicolon-separated list of key:value elements if at least one value is a string.
   *
   * Boolean values will generate the string "true" or "false".
   *
   * @param mixed $o
   * @return string
   */
  function e ($o)
  {
    if (!is_string ($o)) {
      switch (gettype ($o)) {
        case 'boolean':
          return $o ? 'true' : 'false';
        case 'integer':
        case 'double':
          return strval ($o);
        case 'array':
          $at = [];
          $s  = ' ';
          foreach ($o as $k => $v)
            if (is_numeric ($k))
              $at[] = $v;
            else if (is_string ($v)) {
              $at[] = "$k:$v";
              $s    = ';';
            }
            else
              $at[] = $k;
          $o = implode ($s, $at);
          break;
        case 'NULL':
          return '';
        default:
          throw new \InvalidArgumentException ("Can't output a value of type " . gettype ($o));
      }
    }

    return htmlentities ($o, ENT_QUOTES, 'UTF-8', false);
  }

  /**
   * Renders all children and returns the resulting markup.
   * Note: the component itself is not rendered.
   *
   * @return string
   */
  public function getContent ()
  {
    ob_start (null, 0);
    if (!$this->inactive) {
      $this->databind ();
      $this->preRender ();
      $this->renderChildren ();
      $this->postRender ();
    }

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
    return isset($this->attrsObj->$fieldName) || $this->isBound ($fieldName);
  }

  /**
   * Can't be abstract because the child class may not implement IAttributes.
   *
   * @return ComponentAttributes
   */
  public function newAttributes ()
  {
    return null;
  }

  /**
   * Called after the component has been created by the parsing process
   * and all attributes and children have also been parsed.
   * Override this to implement parsing-time behavior.
   */
  public function parsed ()
  {
    //implementation is specific to each component type.
  }

  /**
   * Invokes doRender() recursively on the component's children.
   */
  function renderChildren ()
  {
    if (isset($this->children))
      foreach ($this->children as $child)
        $child->doRender ();
  }

  /**
   * Invokes doRender() recursively on the specified attribute's children.
   * @param string $name
   * @throws ComponentException
   */
  function renderParameter ($name)
  {
    $children = $this->getChildren ($name);
    if (!empty($children))
      foreach ($children as $child)
        $child->doRender ();
  }

  /**
   * Executes the component and any relevant children.
   * Do not override! Use event handlers or override render() or renderChildren().
   */
  public final function run ()
  {
    $this->doRender ();
  }

  /**
   * Runs a private child component that does not belong to the hierarchy.
   *
   * @param Component $c
   */
  public final function runPrivate (Component $c)
  {
    $this->attach ($c);
    $c->run ();
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
   * @see doRender()
   */
  protected function render ()
  {
    //implementation is specific to each component type.
  }

  protected final function runChildren ()
  {
    $this->renderChildren ();
  }

  protected function setAutoId ()
  {
    if ($this->regenerateId || (isset($this->attrsObj) && !isset($this->attrsObj->id))) {
      $this->regenerateId = true; // if the component is re-rendered, always generate an id from now on.
      // Strip non alpha-numeric chars from generated name.
      $this->attrsObj->id =
        preg_replace ('/\W/', '', property ($this->attrsObj, 'name', strtolower ($this->className))) .
        $this->getUniqueId ();
    }

    return $this->attrsObj->id;
  }

}
