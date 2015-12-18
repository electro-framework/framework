<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Type;
use Selenia\Matisse\Component;
use Selenia\Matisse\Context;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\IAttributes;

class MacroInstanceAttributes
{
  public $script;
  public $style;
  /**
   * Points to the component that defines the macro for these attributes.
   * @var Macro
   */
  protected $macro;
  /**
   * Dynamic set of attributes, as specified on the source markup.
   * @var array
   */
  private $attributes;

  public function __construct (Component $component, Macro $macro)
  {
    $this->macro = $macro;
  }

  public function __get ($name)
  {
    if (isset($this->attributes)) {
      $v = get ($this->attributes, $name);
      if (!is_null ($v) && $v != '')
        return $v;
    }
    $macroParam = $this->macro->getParameter ($name);
    if (isset($macroParam->bindings) && array_key_exists ('default', $macroParam->bindings))
      return $macroParam->bindings['default'];

    return $this->getDefault ($name);
  }

  public function __set ($name, $value)
  {
    if (!isset($this->attributes))
      $this->attributes = [$name => $value];
    else
      $this->attributes[$name] = $value;
  }

  public function __isset ($name)
  {
    return isset($this->attributes) && array_key_exists ($name, $this->attributes);
  }

  public function defines ($name)
  {
    return $this->isPredefined ($name) || !is_null ($this->macro->getParameter ($name));
  }

  public function get ($name, $default = null)
  {
    $v = $this->__get ($name);
    if (is_null ($v))
      return $default;

    return $v;
  }

  public function getAll ()
  {
    return $this->attributes;
  }

  public function getAttributeNames ()
  {
    return $this->macro->getParametersNames ();
  }

  public function getDefault ($name)
  {
    $param = $this->macro->getParameter ($name);
    if (is_null ($param))
      throw new ComponentException($this->macro, "Undefined parameter $name.");

    return $this->macro->getParameter ($name)->attrs ()->default;
  }

  public function getScalar ($name)
  {
    return ComponentAttributes::validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  public function getTypeNameOf ($name)
  {
    $t = $this->getTypeOf ($name);
    if (!is_null ($t))
      return ComponentAttributes::$TYPE_NAMES[$t];

    return null;
  }

  public function getTypeOf ($name)
  {
    if ($this->isPredefined ($name)) {
      $fn = "typeof_$name";
      if (method_exists ($this, $fn))
        return $this->$fn();

      return null;
    }

    return $this->macro->getParameterType ($name);
  }

  public function isPredefined ($name)
  {
    return method_exists ($this, "typeof_$name");
  }

  public function set ($name, $value)
  {
    $this->$name = $value;
  }

  public function setScalar ($name, $v)
  {
    /*
      if ($this->isEnum($name)) {
      $enum = $this->getEnumOf($name);
      if (array_search($v,$enum) === FALSE) {
      $list = implode('</b>, <b>',$enum);
      throw new ComponentException($this->component,"Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
      }
      } */
    $this->attributes[$name] = ComponentAttributes::validateScalar ($this->getTypeOf ($name), $v, $name);
  }

  protected function typeof_script ()
  {
    return Type::PARAMS;
  }

  protected function typeof_style ()
  {
    return Type::PARAMS;
  }

}

class MacroInstance extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Points to the component that defines the macro for this instance.
   * @var Macro
   */
  protected $macro;

  public function __construct (Context $context, $tagName, Macro $macro, array $attributes = null)
  {
    $this->macro = $macro; //must be defined before the parent constructor is called
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  /**
   * @see IAttributes::attrs()
   * @return MacroInstanceAttributes
   */
  function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return MacroInstanceAttributes
   */
  function newAttributes ()
  {
    return new MacroInstanceAttributes($this, $this->macro);
  }

  public function parsed ()
  {
    $this->processParameters ();
//    $this->databind ();

    // Move children to default parameter

    if ($this->hasChildren ()) {
      $def = $this->macro->attrs ()->defaultParam;
      if (!empty($def)) {
        $param = $this->macro->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "The macro's declared default parameter is invalid: $def");
        $type = $this->attrsObj->getTypeOf ($def);
        if ($type != Type::SRC && $type != Type::METADATA)
          throw new ComponentException($this,
            "The macro's default parameter <b>$def</b> can't hold content (type: " .
            ComponentAttributes::$TYPE_NAMES[$type] . ").");
        $param                = new Parameter($this->context, ucfirst ($def), $type);
        $this->attrsObj->$def = $param;
        $param->attachTo ($this);
        $param->setChildren ($this->removeChildren ());
      }
    }
    $content = $this->macro->apply ($this);
    $this->replaceBy ($content);
  }

  private function processParameters ()
  {
    $o      = [];
    $styles = $this->attrs ()->style;

    if (isset($styles))
      foreach ($styles as $sheet) {
        if (isset($sheet->attrs ()->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->attrs ()->src,
          ];
        else if (!empty($sheet->children))
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->attrs ()->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->attrs ()->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        if (isset($script->attrs ()->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->attrs ()->src,
          ];
        else if (!empty($script->children))
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->attrs ()->get ('name'),
            'defer' => $script->attrs ()->get ('defer'),
            'data'  => $script,
          ];
      }
    }
    $o = array_reverse ($o);
    foreach ($o as $i)
      switch ($i['type']) {
        case 'sh':
          $this->page->addStylesheet ($i['src'], true);
          break;
        case 'ish':
          $this->page->addInlineCss ($i['data'], $i['name'], true);
          break;
        case 'sc':
          $this->page->addScript ($i['src'], true);
          break;
        case 'isc':
          if ($i['defer'])
            $this->page->addInlineDeferredScript ($i['data'], $i['name'], true);
          else $this->page->addInlineScript ($i['data'], $i['name'], true);
          break;
      }
  }

}
