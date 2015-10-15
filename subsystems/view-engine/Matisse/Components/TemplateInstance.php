<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Context;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\IAttributes;

class TemplateInstanceAttributes
{

  public $script;
  public $style;
  /**
   * Points to the component that defines the template for these attributes.
   * @var Template
   */
  protected $template;
  /**
   * Dynamic set of attributes, as specified on the source markup.
   * @var array
   */
  private $attributes;

  public function __construct (Component $component, Template $template)
  {
    $this->template = $template;
  }

  public function __get ($name)
  {
    if (isset($this->attributes)) {
      $v = get ($this->attributes, $name);
      if (!is_null ($v) && $v != '')
        return $v;
    }
    $templateParam = $this->template->getParameter ($name);
    if (isset($templateParam->bindings) && array_key_exists ('default', $templateParam->bindings))
      return $templateParam->bindings['default'];

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

  public function get ($name, $default = null)
  {
    $v = $this->__get ($name);
    if (is_null ($v))
      return $default;

    return $v;
  }

  public function set ($name, $value)
  {
    $this->$name = $value;
  }

  public function getTypeOf ($name)
  {
    if ($this->isPredefined ($name)) {
      $fn = "typeof_$name";
      if (method_exists ($this, $fn))
        return $this->$fn();

      return null;
    }

    return $this->template->getParameterType ($name);
  }

  public function defines ($name)
  {
    return $this->isPredefined ($name) || !is_null ($this->template->getParameter ($name));
  }

  public function isPredefined ($name)
  {
    return method_exists ($this, "typeof_$name");
  }

  public function getAttributeNames ()
  {
    return $this->template->getParametersNames ();
  }

  public function getAll ()
  {
    return $this->attributes;
  }

  public function getTypeNameOf ($name)
  {
    $t = $this->getTypeOf ($name);
    if (!is_null ($t))
      return ComponentAttributes::$TYPE_NAMES[$t];

    return null;
  }

  public function getScalar ($name)
  {
    return ComponentAttributes::validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  public function getDefault ($name)
  {
    $param = $this->template->getParameter ($name);
    if (is_null ($param))
      throw new ComponentException($this->template, "Undefined parameter $name.");

    return $this->template->getParameter ($name)->attrs ()->default;
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
    return AttributeType::PARAMS;
  }

  protected function typeof_style ()
  {
    return AttributeType::PARAMS;
  }

}

class TemplateInstance extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Points to the component that defines the template for this instance.
   * @var Template
   */
  protected $template;

  public function __construct (Context $context, $tagName, Template $template, array $attributes = null)
  {
    $this->template = $template; //must be defined before the parent constructor is called
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  /**
   * @see IAttributes::attrs()
   * @return TemplateInstanceAttributes
   */
  function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return TemplateInstanceAttributes
   */
  function newAttributes ()
  {
    return new TemplateInstanceAttributes($this, $this->template);
  }

  public function parsed ()
  {
    $this->processParameters ();
//    $this->databind ();

    // Move children to default parameter

    if (!empty($this->children)) {
      $def = $this->template->attrs ()->defaultParam;
      if (!empty($def)) {
        $param = $this->template->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "The template's declared default parameter is invalid: $def");
        $type = $this->attrsObj->getTypeOf ($def);
        if ($type != AttributeType::SRC && $type != AttributeType::METADATA)
          throw new ComponentException($this,
            "The template's default parameter <b>$def</b> can't hold content (type: " .
            ComponentAttributes::$TYPE_NAMES[$type] . ").");
        $param                = new Parameter($this->context, ucfirst ($def), $type);
        $this->attrsObj->$def = $param;
        $param->attachTo ($this);
        $param->setChildren ($this->children, false);
        $this->children = [];
      }
    }
    $content = $this->template->apply ($this);
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

//    if (isset($styles))
//      foreach ($styles as $sheet) {
//        if (isset($sheet->attrs ()->src))
//          $this->page->addStylesheet ($sheet->attrs ()->src);
//        else if (!empty($sheet->children)) {
//          $name = $sheet->attrs ()->get ('name');
//          $this->page->addInlineCss ($sheet, $name);
//        }
//      }
//    $scripts = $this->attrs ()->script;
//    if (isset($scripts)) {
//      foreach ($scripts as $script) {
//        if (isset($script->attrs ()->src))
//          $this->page->addScript ($script->attrs ()->src);
//        else if (!empty($script->children)) {
//          $name = $script->attrs ()->get ('name');
//          $this->page->addInlineScript ($script, $name);
//        }
//      }
//    }

  }

}
