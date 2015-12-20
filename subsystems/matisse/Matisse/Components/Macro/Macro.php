<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Attributes\Macro\MacroAttributes;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Parameter;
use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\IAttributes;

class Macro extends Component implements IAttributes
{
  /** Finds binding expressions which are not macro bindings. */
  const FIND_NON_MACRO_EXP = '#\{\{\s*(?=\S)[^@]#u';
  /** Finds macro binding expressions. */
  const PARSE_MACRO_BINDING_EXP = '#\{\{\s*@(.*?)\s*\}\}#u';

  public $allowsChildren = true;

  private static function evalScalarExp ($bindExp, MacroInstance $instance, &$transfer_binding = null)
  {
    $transfer_binding = false;
    if (self::isCompositeBinding ($bindExp))
      return preg_replace_callback (self::PARSE_MACRO_BINDING_EXP,
        function ($args) use ($instance, $transfer_binding) {
          return self::evalScalarRef ($args[1], $instance, $transfer_binding);
        }, $bindExp);
    throw new \Exception("TO DO: upgrade identifier extract formula (see source code)");
    $bindExp = substr ($bindExp, 2, strlen ($bindExp) - 3);

    return self::evalScalarRef ($bindExp, $instance, $transfer_binding);
  }

  private static function evalScalarRef ($ref, MacroInstance $instance, &$transfer_binding)
  {
    $ref = normalizeAttributeName ($ref);
    if (isset($instance->bindings) && array_key_exists ($ref, $instance->bindings)) {
      $transfer_binding = true;

      return $instance->bindings[$ref];
    }
    $value = $instance->attrs ()->$ref;
    if (self::isBindingExpression ($value))
      return $value;
    $value = $instance->attrs ()->getScalar ($ref);
    if (is_null ($value) || $value === '')
      $value = $instance->attrs ()->getDefault ($ref);

    return $value;
  }

  private static function parsingtimeDatabind (Component $component, MacroInstance $instance, $force = false)
  {
    if (isset($component->bindings))
      foreach ($component->bindings as $attrName => $bindExp) {
        $value            = self::evalScalarExp ($bindExp, $instance);
        $transfer_binding = self::isBindingExpression ($value);
        if ($transfer_binding) {
          if ($force) {
            //$value = $component->evalBinding($value);
            //$component->attrs()->$attrName = $value;
            $component->removeBinding ($attrName);
          }
          $component->addBinding ($attrName, $value); //replace current binding
        }
        else {
          //final value is not a binding exp.
          $component->attrs ()->$attrName = $value;
          $component->removeBinding ($attrName);
        }
      }
    if ($component->hasChildren ())
      foreach ($component->getChildren () as $child)
        self::parsingtimeDatabind ($child, $instance, $force || $component instanceof Parameter);
  }

  public function apply (MacroInstance $instance)
  {
    $o      = [];
    $styles = $this->attrs ()->style;
    if (isset($styles))
      foreach ($styles as $sheet) {
        self::parsingtimeDatabind ($sheet, $instance);
        if (isset($sheet->attrs ()->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->attrs ()->src,
          ];
        else if (!empty($sheet->getChildren()))
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->attrs ()->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->attrs ()->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        self::parsingtimeDatabind ($script, $instance);
        if (isset($script->attrs ()->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->attrs ()->src,
          ];
        else if (!empty($script->getChildren()))
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->attrs ()->get ('name'),
            'defer' => $script->attrs ()->get ('defer'),
            'data'  => $script,
          ];
      }
    }
    //$o = array_reverse ($o);
    foreach ($o as $i)
      switch ($i['type']) {
        case 'sh':
          $instance->page->addStylesheet ($i['src'], false);
          break;
        case 'ish':
          $instance->page->addInlineCss ($i['data'], $i['name'], false);
          break;
        case 'sc':
          $instance->page->addScript ($i['src'], false);
          break;
        case 'isc':
          if ($i['defer'])
            $instance->page->addInlineDeferredScript ($i['data'], $i['name'], false);
          else $instance->page->addInlineScript ($i['data'], $i['name'], false);
          break;
      }

//    $styles = $this->attrs ()->get ('style');
//    if (isset($styles))
//      foreach ($styles as $sheet) {
//        self::parsingtimeDatabind ($sheet, $instance);
//        if (isset($sheet->attrs ()->src))
//          $instance->page->addStylesheet ($sheet->attrs ()->src);
//        else if (!empty($sheet->children)) {
//          $name = $sheet->attrs ()->get ('name');
//          $instance->page->addInlineCss ($sheet, $name);
//        }
//      }
//    $scripts = $this->attrs ()->get ('script');
//    if (isset($scripts)) {
//      foreach ($scripts as $script) {
//        self::parsingtimeDatabind ($script, $instance);
//        if (isset($script->attrs ()->src))
//          $instance->page->addScript ($script->attrs ()->src);
//        else if (!empty($script->children)) {
//          $name = $script->attrs ()->get ('name');
//          $instance->page->addInlineScript ($script, $name);
//        }
//      }
//    }

    $cloned = $this->getClonedChildren ();
    $this->applyTo ($cloned, $instance);

    return $cloned;
  }

  public function applyTo (array &$components = null, MacroInstance $instance)
  {
    if (!is_null ($components))
      for ($i = 0; $i < count ($components); ++$i) {
        $component = $components[$i];
        if (!is_null ($component)) {
          if (isset($component->bindings)) {
            foreach ($component->bindings as $field => $exp) {
              if (preg_match (self::PARSE_MACRO_BINDING_EXP, $exp, $match)) {
                //evaluate macro binding expression
                if (preg_match (self::FIND_NON_MACRO_EXP, $exp)) {
                  //mixed (data/macro) binding
                  $component->addBinding ($field,
                    self::evalScalarExp ($exp, $instance)); //replace current binding
                }
                else {
                  if ($exp[0] != '{' || substr ($exp, -1) != '}' ||
                      strpos ($exp, '}') < strlen ($exp) - 2
                  ) {
                    //composite exp. (constant text + binding ref)
                    $value = self::evalScalarExp ($exp, $instance, $transfer_binding);
                    if ($transfer_binding)
                      $component->addBinding ($field, $value); //replace current binding
                    else {
                      //final value is not a binding exp.
                      $component->attrs ()->$field = $value;
                      $component->removeBinding ($field);
                    }
                  }
                  else {
                    //simple exp. (binding ref. only}
                    $attrName = $match[1];
                    if (!$instance->attrs ()->defines ($attrName)) {
                      $s = join (', ', $instance->attrs ()->getAttributeNames ());
                      throw new ComponentException($instance,
                        "<p>The parameter <b>$attrName</b>, specified on a call to/in the <b>{$this->attrs ()->name}</b> macro, is not defined on that macro.</p>
<table>
  <th>Expected parameters:<td>$s
  <tr><th>Instance:<td>{$instance->getTagName ()}
</table>");
                    }
                    if (isset($this->bindings) && array_key_exists ($attrName, $this->bindings))
                      $content = $this->bindings[$attrName];
                    else $content = $instance->attrs ()->$attrName;
                    if (isset($instance->bindings) &&
                        array_key_exists ($attrName, $instance->bindings)
                    ) {
                      //transfer binding from the macro instance to the component
                      $component->addBinding ($field, $instance->bindings[$attrName]);
                      continue;
                    }
                    $value = $content instanceof Parameter ? $content->getValue () : $content;
                    if ($component instanceof Literal) {
                      if (is_array ($value)) {
                        //replace literal by a component set
                        array_splice ($components, $i, 1, $value);
                        $i += count ($value) - 1;
                        continue;
                      }
                      if (!self::isBindingExpression ($value))
                        //convert boolean value to string, only for literals
                        if ($instance->attrs ()->getTypeOf ($attrName) == type::bool)
                          $value =
                            ComponentAttributes::validateScalar (type::bool, $value)
                              ? 'true' : 'false';
                    }
                    if (self::isBindingExpression ($value)) {
                      //assign new binding expression to target component
                      $component->addBinding ($field, $value);
                    }
                    else {
                      $component->attrs ()->$field = $value;
                      $component->removeBinding ($field);
                    }
                  }
                }
              }
            }
          }
          $attrs  = $component->attrs ()->getAttributesOfType (type::parameter);
          $values = array_values ($attrs);
          $this->applyTo ($values, $instance);
          $attrs  = $component->attrs ()->getAttributesOfType (type::multipleParams);
          $values = array_values ($attrs);
          foreach ($values as $paramArray)
            $this->applyTo ($paramArray, $instance);
          $this->applyTo ($component->getChildrenRef (), $instance);
        }
      }
  }

  /**
   * @see IAttributes::attrs()
   * @return MacroAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Returns the macro parameter with the given name.
   * @param string $name
   * @return Parameter
   */
  public function getParameter ($name)
  {
    $name   = denormalizeAttributeName ($name);
    $params = $this->attrs ()->get ('param');
    if (!is_null ($params))
      foreach ($params as $param)
        if ($param->attrs ()->name == $name)
          return $param;

    return null;
  }

  public function getParameterType ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $p = type::getIdOf($param->attrs ()->type);
      if ($p === false) {
        $s = join ('</b>, <b>', array_slice (ComponentAttributes::$TYPE_NAMES, 1));
        throw new ComponentException($this,
          "The type attribute for the <b>$name</b> parameter is invalid.\nExpected values: <b>$s</b>.");
      }

      return $p;
    }

    return null;
  }

  public function getParametersNames ()
  {
    $params = $this->attrs ()->get ('param');
    if (is_null ($params)) return null;
    $names = [];
    foreach ($params as $param)
      $names[] = $param->attrs ()->name;

    return $names;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return MacroAttributes
   */
  public function newAttributes ()
  {
    return new MacroAttributes($this);
  }

  public function parsed ()
  {
    $this->context->addMacro ($this->attrs ()->name, $this);
  }
}
