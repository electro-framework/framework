<?php
namespace Selene\Matisse;
use Selene\Matisse\Components\Literal;
use Selene\Matisse\Components\Page;
use Selene\Matisse\Components\Parameter;
use Selene\Matisse\Components\TemplateInstance;
use Selene\Matisse\Exceptions\ComponentException;
use Selene\Matisse\Exceptions\DataBindingException;
use Selene\Matisse\Exceptions\FileIOException;
use Selene\Matisse\Exceptions\HandlerNotFoundException;
use Selene\Matisse\Exceptions\ParseException;

/**
 * The base class from which all components derive.
 */
abstract class Component
{
  const PARSE_PARAM_BINDING_EXP = '#\{ \s* (?: \! ([^.}{]*) )? \.? ( (?: [^{}]* | \{ [^{}]* \} )* ) \}#x';
  /**
   * An array containing the instance creation counters for each component class name.
   *
   * @var array
   */
  protected static $uniqueIDs = [];
  /**
   * An array containing the names of the HTML tags which must not have a closing tag.
   *
   * @var array
   */
  private static $VOID_ELEMENTS = [
    'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source',
    'track', 'wbr'
  ];
  /**
   * Indicates if the component supports the IAttributes interface.
   *
   * @var boolean
   */
  public $supportsAttributes;
  /**
   * The value of the tag being currently outputted.
   *
   * @var string
   */
  public $content;
  /**
   * Points to the parent component in the page hierarchy.
   * It is set to NULL if the component is the top one (a Page instance) or if it's standalone.
   *
   * @var Component
   */
  public $parent = null;
  /**
   * An array of child components that are either defined on the source code or
   * generated dinamically.
   *
   * @var Component[]
   */
  public $children = null;
  /**
   * The namespace of the component's tag.
   *
   * @var string
   */
  public $namespace = 'c';
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
   * An array of attribute names and corresponding databinding expressions.
   * Equals NULL if no bindings are defined.
   *
   * @var array
   */
  public $bindings = null;
  /**
   * Supplies the value for databinding expressions with no explicit data source references.
   *
   * @var mixed
   */
  public $defaultDataSource;
  /**
   * The attribute name to be used when content is placed immediatly after a component's opening tag, without
   * explicitly specifying a `<p:xxx>` parameter.
   * This should be specified in snake-case (ex: if-set -> if_set).
   *
   * @var string
   */
  public $defaultAttribute = null;
  /**
   * Set by Repeater components for supporting pagination.
   * @var int
   */
  public $rowOffset = 0;
  /**
   * Points to the root of the components tree.
   *
   * @var Page
   */
  public $page;
  /**
   * The rendering context for the current request.
   * @var Context
   */
  protected $context;
  /**
   * Set to true on a component class definition to automatically assign an ID to instances.
   *
   * @see setAutoId().
   * @var bool
   */
  protected $autoId = false;
  /**
   * The component's published properties (the ones which are settable through xml attribute declarations on the source
   * markup). This property contains an object of class ComponentAttributes or of a subclass of it, depending on the
   * component class of the instance. Do not access this property directly. @see Component::attrs()
   *
   * @var ComponentAttributes
   */
  protected $attrsObj;
  /**
   * Cache for getTagName()
   *
   * @var string
   */
  private $tagName;
  private $tags = [];
  private $tag;
  /**
   * When true, forces generation of a new auto-id, event if the component already has an assigned id.
   *
   * @var bool
   */
  private $regenerateId = false;

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
      if ($attributes)
        foreach ($attributes as $name => $value)
          $this->attrsObj->set ($name, $value);
    }
    else if ($attributes)
      throw new ComponentException($this, 'This component does not support attributes.');
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
   * Creates a component corresponding to the specified tag and optionally sets its attributes.
   *
   * @param Context   $context
   * @param Component $parent
   * @param string    $tagName
   * @param array     $attributes
   * @param boolean   $strict If true, failure to find a component class will throw an exception.
   *                          If false, an attempt is made to load a template with the same name,
   * @return Component Component instance. For templates, an instance of Template is returned.
   *                          You should them
   * @throws ComponentException
   * @throws ParseException
   */
  static public function create (Context $context, Component $parent, $tagName, array $attributes = null,
                                 $strict = false)
  {
    $class = $context->getClassForTag ($tagName);
    if (!$class) {
      if ($strict)
        self::throwUnknownComponent ($context, $tagName);

      // Component class not found.
      // Try to load a template with the same tag name.

      $template = $context->getTemplate ($tagName);
      try {
        if (is_null ($template))
          $template = self::loadTemplate ($context, $parent, $tagName);
      } catch (FileIOException $e) {
        self::throwUnknownComponent ($context, $tagName);
      }
      $component = new TemplateInstance($context, $tagName, $template, $attributes);
    }

    // Component class was found.

    else $component = new $class ($context, $attributes);

    // For both types of components:

    $component->setTagName ($tagName); //for performance
    return $component;
  }

  static function loadTemplate (Context $context, Component $parent, $tagName)
  {
    $filename = normalizeTagName ($tagName) . '.xml';
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

  public static function isCompositeBinding ($exp)
  {
    return $exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '{', 1) > 0;
  }

  public static function isBindingExpression ($exp)
  {
    return is_string ($exp) ? strpos ($exp, '{') !== false : false;
  }

  /**
   * @param Component[] $components
   */
  public static function inspectSet (array $components = null)
  {
    if (is_array ($components))
      foreach ($components as $component)
        /** @var Component $component */
        $component->inspect ();
  }

  /**
   * @param Component[] $components
   * @param Component   $parent
   * @return Component[]|null
   */
  public static function cloneComponents (array $components = null, Component $parent = null)
  {
    if (isset($components)) {
      $result = [];
      foreach ($components as $component) {
        /** @var Component $cloned */
        $cloned = clone $component;
        if (isset($parent))
          $cloned->attachTo ($parent);
        else $cloned->detach ();
        $result[] = $cloned;
      }
      return $result;
    }
    return null;
  }

  protected static function runSet (array $components = null)
  {
    self::renderSet ($components);
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

  private static function throwUnknownComponent (Context $context, $tagName)
  {
    $paths = implode ('', map ($context->templateDirectories,
      function ($dir) { return "<li><path>$dir</path></li>"; }));
    throw new ComponentException (null,
      "<h3>Unknown tag: <b>&lt;c:$tagName></b></h3>
<p>Neither a class nor a template implementing that tag were found.
<p>Perhaps you forgot to register the tag?
<p>If it's a template, Matisse is searching for it on these paths:<ul>$paths</ul>");
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
    return $this->tagName = strtolower (implode ('-', $matches[0]));
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

  public final function getQualifiedName ()
  {
    return $this->namespace . ':' . $this->getTagName ();
  }

  public function __get ($name)
  {
    throw new ComponentException($this, "Can't read from non existing property <b>$name</b>.");
  }

  public function __set ($name, $value)
  {
    throw new ComponentException($this, "Can't set non existing property <b>$name</b>.");
  }

  /**
   * Replaces the component by its contents in the parent's child list.
   * The component itself is therefore discarded from the components tree.
   */
  public final function replaceByContents ()
  {
    $this->replaceBy ($this->children);
  }

  /**
   * Returns the ordinal index of this component on the parent's child list.
   *
   * @return int|boolean
   * @throws ComponentException
   */
  public function getIndex ()
  {
    if (!isset($this->parent))
      throw new ComponentException($this, "The component is not attached to a parent.");
    if (!isset($this->parent->children))
      throw new ComponentException($this, "The parent component has no children.");
    return array_search ($this, $this->parent->children, true);
  }

  /**
   * Returns the ordinal index of the specified child on this component's child list.
   *
   * @param Component $child
   * @return bool|int
   */
  public function indexOf (Component $child)
  {
    return array_search ($child, $this->children, true);
  }

  /**
   * Replaces the component by the specified componentes in the parent's child list.
   * The component itself is discarded from the components tree.
   *
   * @param array $components
   * @throws ComponentException
   */
  public final function replaceBy (array $components = null)
  {
    $p = $this->getIndex ();
    if ($p !== false) {
      array_splice ($this->parent->children, $p, 1, $components);
      $this->parent->attach ($components);
    }
    else {
      ob_start ();
      self::inspectSet ($this->parent->children);
      $t = ob_get_clean ();
      throw new ComponentException($this,
        "The component was not found on the parent's children.<h3>The children are:</h3><fieldset>$t</fieldset>");
    }
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

  /**
   * Executes the component and any relevant children.
   * Do not override! Use event handlers or override render() or renderChildren().
   */
  public final function run ()
  {
    $this->doRender ();
  }

  public final function addChild (Component $child)
  {
    if (isset($child)) {
      $this->children[] = $child;
      $this->attach ($child);
    }
  }

  public final function addChildren (array $children = null)
  {
    if (isset($children))
      foreach ($children as $child)
        $this->addChild ($child);
  }

  public function remove ()
  {
    if (isset($this->parent))
      $this->parent->removeChild ($this);
  }

  public function removeChild (Component $child)
  {
    $p = $this->indexOf ($child);
    if ($p === false)
      throw new ComponentException($child,
        "The component is not a child of the specified parent, so it cannot be removed.");
    array_splice ($this->children, $p, 1);
    $child->detach ();
  }

  public function attachTo (Component $parent = null)
  {
    $this->parent = $parent;
    $this->page   = $parent->page;
  }

  /**
   * @param Component|Component[] $childOrChildren
   */
  public function attach ($childOrChildren = null)
  {
    if (!empty($childOrChildren)) {
      if (is_array ($childOrChildren))
        foreach ($childOrChildren as $child)
          /** @var Component $child */
          $child->attachTo ($this);
      else $childOrChildren->attachTo ($this);
    }
  }

  public function detach ()
  {
    $this->parent = $this->page = null;
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
      if (!isset($this->attrsObj) || $this->attrsObj->visible) {
        $this->preRender ();
        $this->render ();
        $this->postRender ();
      }
    }
  }

  /**
   * Renders all children and returns the resulting markup.
   * Note: the component itself is not rendered.
   *
   * @return string
   */
  public function getContent ()
  {
    ob_start ();
    if (!$this->inactive) {
      $this->databind ();
      $this->preRender ();
      $this->renderChildren ();
      $this->postRender ();
    }
    return ob_get_clean ();
  }

  protected function setContent ($content)
  {
    if (!$this->tag->isContentSet) {
      echo '>';
      if ($this->context->debugMode)
        echo "\n";
    }
    echo $content;
    $this->tag->isContentSet = true;
  }

  /**
   * Invokes doRender() recursively on the component's children.
   * Override to restrict the set of children which are affected.
   */
  public final function renderChildren ()
  {
    if (isset($this->children))
      foreach ($this->children as $child)
        $child->doRender ();
  }

  /**
   * Renders a child parameter.
   * This method is invoked by the child when being rendered.
   *
   * @param Parameter $param
   */
  public function renderParameter (Parameter $param)
  {
    //implementation is optional
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

  public final function getChildren ($attrName)
  {
    if (isset($this->attrsObj->$attrName)) {
      $p = $this->attrsObj->$attrName;
      if ($p instanceof Parameter)
        return $p->children;
      throw new ComponentException($this,
        "Can' get children of attribute <b>$attrName</b>, which has a value of type <b>" . gettype ($p) . '</b>.');
    }
    return null;
  }

  public final function setChildren (array $children = null)
  {
    if (isset($children)) {
      $this->children = $children;
      $this->attach ($children);
    }
  }

  public final function getClonedChildren ($attrName)
  {
    return self::cloneComponents ($this->getChildren ($attrName));
  }

  public final function isBound ($fieldName)
  {
    return isset($this->bindings) && array_key_exists ($fieldName, $this->bindings);
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
   * Registers a data binding.
   *
   * @param string $attrName The name of the bound attribute.
   * @param string $bindExp  The binding expression.
   */
  public final function addBinding ($attrName, $bindExp)
  {
    if (!isset($this->bindings))
      $this->bindings = [];
    $this->bindings[$attrName] = $bindExp;
  }

  public final function removeBinding ($attrName)
  {
    if (isset($this->bindings)) {
      unset($this->bindings[$attrName]);
      if (empty($this->bindings))
        $this->bindings = null;
    }
  }

  public final function inspect ($deep = true)
  {
    echo '<pre style="background-color:#FFF">&lt;<b>' . $this->getQualifiedName () . '</b>';
    if (!isset($this->parent))
      echo ' <b style="color:#F00">NO PARENT</b>';
    if ($this->supportsAttributes) {
      $props = $this->attrsObj->getAll ();
      if (isset($props))
        foreach ($props as $k => $v)
          if (isset($v)) {
            $t = $this->attrsObj->getTypeOf ($k);
            if (!$deep || ($t != AttributeType::SRC && $t != AttributeType::PARAMS)) {
              $tn = $this->attrsObj->getTypeNameOf ($k);
              echo "\n   $k: <i style='color:#00C'>$tn</i> = ";
              switch ($t) {
                case AttributeType::BOOL:
                  echo '<i>' . ($v ? 'TRUE' : 'FALSE') . '</i>';
                  break;
                case AttributeType::ID:
                  echo "\"$v\"";
                  break;
                case AttributeType::NUM:
                  echo $v;
                  break;
                case AttributeType::TEXT:
                  echo "\"<span style='color:#888'>" . str_replace ("\n", '&#8626;', htmlspecialchars ($v)) .
                       '</span>"';
                  break;
                default:
                  if (is_object ($v))
                    echo '<i>object</i>';
                  else if (is_array ($v))
                    echo '<i>array</i>';
                  else
                    echo "\"$v\"";
              }
            }
          }
    }
    echo "&gt;<blockquote>";
    if (isset($this->bindings)) {
      echo "<div style='background-color:#EFF;padding:4px;border:1px solid #DDD'><b>Bindings</b>:<ul>";
      foreach ($this->bindings as $k => $v)
        echo "<li>$k = <span style='color:#800'>" . htmlspecialchars ($v) . '</span></li>';
      echo '</ul></div>';
    }
    if ($deep) {
      if ($this->supportsAttributes) {
        if (isset($props))
          foreach ($props as $k => $v)
            if (isset($v)) {
              $t = $this->attrsObj->getTypeOf ($k);
              if ($t == AttributeType::SRC || $t == AttributeType::PARAMS) {
                $tn = $this->attrsObj->getTypeNameOf ($k);
                echo "<p style='border:1px solid #ccc;padding:8px;background-color:#eee;margin-bottom:-1px'><b>$k</b>: <i style='color:#00C'>$tn</i></p><div style='border:1px solid #ccc;padding:8px'>";
                switch ($t) {
                  case AttributeType::SRC:
                    $x = $this->attrsObj->$k->children;
                    if (isset($x))
                      foreach ($x as $c)
                        /** @var Component $c */
                        $c->inspect ();
                    break;
                  case AttributeType::PARAMS:
                    self::inspectSet ($this->attrsObj->$k);
                    break;
                }
                echo '</div>';
              }
            }
      }
      if (isset($this->children)) {
        $b = isset($this->parent) && $this->className != 'Parameter';
        if ($b)
          echo '<h3 style="border:1px solid #ccc;padding:8px;background-color:#eee;margin-bottom:-1px">Generated children</h3><div style="border:1px solid #ccc;padding:8px">';
        foreach ($this->children as $c)
          $c->inspect ();
        if ($b)
          echo '</div>';
      }
    }
    echo "</blockquote></pre>";
  }

  public function __clone ()
  {
    if (isset($this->attrsObj)) {
      $this->attrsObj = clone $this->attrsObj;
      $this->attrsObj->setComponent ($this);
    }
    if (isset($this->children))
      $this->children = self::cloneComponents ($this->children, $this);
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

  protected final function runChildren ()
  {
    $this->renderChildren ();
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

  protected function preRender ()
  {
    //stub
  }

  protected function postRender ()
  {
    //stub
  }

  /**
   * Returns the data source to be used for non qualified databinging expressions.
   * Searches upwards on the component hierarchy.
   *
   * @return DataSource
   */
  protected function getDefaultDataSource ()
  {
    return isset($this->defaultDataSource)
      ? $this->defaultDataSource
      :
      (isset($this->parent) ? $this->parent->getDefaultDataSource () : null);
  }

  protected function databind ()
  {
    if (isset($this->bindings))
      foreach ($this->bindings as $attrName => $bindExp) {
        $this->bindToAttribute ($attrName, $this->evalBinding ($bindExp));
      };
  }

  protected function evalBinding ($bindExp)
  {
    if (!is_string ($bindExp))
      return $bindExp;
    try {
      $z = 0;
      do {
        if (self::isCompositeBinding ($bindExp)) {
          //composite expression
          $bindExp = preg_replace_callback (self::PARSE_PARAM_BINDING_EXP, [$this, 'evalBindingExp'], $bindExp);
          if (!self::isBindingExpression ($bindExp))
            return $bindExp;
        }
        else {
          //simple expression
          preg_match (self::PARSE_PARAM_BINDING_EXP, $bindExp, $matches);
          $bindExp = $this->evalBindingExp ($matches, true);
          if (!self::isBindingExpression ($bindExp))
            return $bindExp;
        }
        if (++$z > 10)
          throw new DataBindingException($this,
            "The maximum nesting depth for a data binding expression was exceeded.<p>The last evaluated expression is   <b>$bindExp</b>");
      } while (true);
    } catch (\InvalidArgumentException $e) {
      throw new DataBindingException($this, "Invalid databinding expression: $bindExp\n" . $e->getMessage ());
    }
  }

  protected function bindToAttribute ($name, $value)
  {
    if (is_object ($value))
      $this->attrsObj->$name = $value;
    else $this->attrsObj->set ($name, $value);
  }

  protected function evalBindingExp ($matches, $allowFullSource = false)
  {
    if (empty($matches))
      throw new \InvalidArgumentException();
    list($full, $dataSource, $dataField) = $matches;
    $dataSource = trim ($dataSource);
    $dataField  = trim ($dataField);
    $p          = strpos ($dataField, '{');
    if ($p !== false && $p >= 0) {
      //recursive binding expression
      $exp = preg_replace_callback (self::PARSE_PARAM_BINDING_EXP, [$this, 'evalBindingExp'], $dataField);
      $z   = strpos ($exp, '.');
      if ($z !== false) {
        $dataSource .= substr ($exp, 0, $z);
        $dataField = substr ($exp, $z + 1);
        return "{!$dataSource.$dataField}";
      }
      else
        return empty($dataSource) ? '{' . "$exp}" : "{!$dataSource" . ($p == 0 ? '' : '.') . "$exp}";
    }
    if (empty($dataSource))
      $src = $this->getDefaultDataSource ();
    else {
      $src = get ($this->context->dataSources, $dataSource);
      if (!isset($src))
        throw new DataBindingException($this, "Data source <b>$dataSource</b> is not defined.");
    }
    if ($dataField == '') {
      if ($allowFullSource)
        return $src;
      throw new DataBindingException($this,
        "The full data source reference <b>$full</b> cannot be used on a composite databinding expression.");
    }
    if (is_null ($src))
      return null;
    if (!method_exists ($src, 'getIterator'))
      throw new DataBindingException($this,
        'Data source ' . (empty($dataSource) ? '<i>default</i>' : "<b>$dataSource</b>") .
        ' is not a valid DataSource object.');
    $it = $src->getIterator ();
    /** @var \Iterator $it */
    if (!$it->valid ())
      return null;
    switch ($dataField) {
      case '#key':
        return $it->key ();
      case '#ord':
        return $it->key () + 1 + $this->rowOffset;
      case '#alt':
        return $it->key () % 2;
    }
    $rec = $it->current ();
    if (is_null ($rec)) {
      $it->rewind ();
      $rec = $it->current ();
    }
    if (is_null ($rec))
      $rec = new \EmptyIterator();
    $pipes     = preg_split ('/\s*\|\s*/', $dataField);
    $dataField = array_shift ($pipes);
    $v         = $dataField == '#self' ? $rec : getField ($rec, $dataField);
    foreach ($pipes as $name) {
      $pipe = $this->context->getPipe (trim ($name));
      try {
        $v = call_user_func ($pipe, $v, $this->context);
      } catch (HandlerNotFoundException $e) {
        throw new ComponentException ($this, "Pipe <b>$name</b> was not found.");
      }
    }

    return $v;
  }

  protected function getUniqueId ()
  {
    if (array_key_exists ($this->className, self::$uniqueIDs))
      return ++self::$uniqueIDs[$this->className];
    self::$uniqueIDs[$this->className] = 1;
    return 1;
  }

  protected function beginCapture ()
  {
    ob_start ();
  }

  protected function getLiteral ()
  {
    $this->beginContent ();
    $text = ob_get_clean ();
    if (strlen ($text))
      return Literal::from ($this->context, $text);
    return null;
  }

  protected function endCapture ()
  {
    $literal = $this->getLiteral ();
    if (isset($literal))
      $this->addChild ($literal);
  }

  protected function flushCapture ()
  {
    $this->endCapture ();
    ob_start ();
  }

  protected function beginTag ($name, array $attributes = null)
  {
    if (isset($this->tag)) {
      $this->beginContent ();
      array_push ($this->tags, $this->tag);
    }
    $this->tag       = new Tag();
    $this->tag->name = strtolower ($name);
    echo '<' . $name;
    if ($attributes)
      foreach ($attributes as $k => $v)
        $this->addAttribute ($k, $v);
  }

  protected function endTag ()
  {
    if (is_null ($this->tag))
      throw new ComponentException($this, "Unbalanced beginTag() / endTag() pairs.");
    $name = $this->tag->name;
    $x    = $this->context->debugMode ? "\n" : '';
    if ($this->tag->isContentSet)
      echo "</$name>$x";
    elseif (array_search ($name, self::$VOID_ELEMENTS) !== false)
      echo "/>$x";
    else
      echo "></$name>$x";
    $this->tag = array_pop ($this->tags);
  }

  protected function addTag ($name, array $parameters = null, $content = null)
  {
    $this->beginTag ($name, $parameters);
    if (!is_null ($content))
      $this->setContent ($content);
    $this->endTag ();
  }

  protected function addAttributes ($attrs)
  {
    foreach ($attrs as $name => $val)
      $this->addAttribute ($name, $val);
  }

  protected function addAttribute ($name, $value = '')
  {
    echo isset($value) ? (strlen ($value) ? " $name=\"$value\"" : " $name") : '';
  }

  protected function addAttribute2 ($name, $value1, $value2)
  {
    if (strlen ($value2))
      echo " $name=\"$value1$value2\"";
  }

  protected function addAttributeIf ($checkValue, $name, $value = '')
  {
    if ($checkValue)
      $this->addAttribute ($name, $value);
  }

  protected function beginAttribute ($name, $value = null, $attrSep = ' ')
  {
    if (strlen ($value) == 0) {
      $this->tag->attrName     = " $name=\"";
      $this->tag->isFirstValue = true;
    }
    else
      echo " $name=\"$value";
    $this->tag->attrSep = $attrSep;
  }

  protected function addAttributeValue ($value)
  {
    if (strlen ($value)) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value;
    }
  }

  protected function addAttributeValue2 ($value1, $value2)
  {
    if (strlen ($value2)) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value1 . $value2;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value1 . $value2;
    }
  }

  protected function addAttributeValueIf ($checkValue, $value)
  {
    if ($checkValue) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value;
    }
  }

  protected function endAttribute ()
  {
    if ($this->tag->attrName != '')
      $this->tag->attrName = '';
    else
      echo '"';
    $this->tag->isFirstValue = false;
  }

  protected function beginContent ()
  {
    if (isset($this->tag) && !$this->tag->isContentSet) {
      echo '>';
      if ($this->context->debugMode)
        echo "\n";
      $this->tag->isContentSet = true;
    }
  }

}
