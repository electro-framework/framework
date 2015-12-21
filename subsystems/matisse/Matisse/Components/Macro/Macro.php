<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Macro\MacroProperties;
use Selenia\Matisse\Properties\Types\type;

class Macro extends Component implements PropertiesInterface
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
    $value = $instance->props ()->$ref;
    if (self::isBindingExpression ($value))
      return $value;
    $value = $instance->props ()->getScalar ($ref);
    if (is_null ($value) || $value === '')
      $value = $instance->props ()->getDefault ($ref);

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
            //$component->props()->$attrName = $value;
            $component->removeBinding ($attrName);
          }
          $component->addBinding ($attrName, $value); //replace current binding
        }
        else {
          //final value is not a binding exp.
          $component->props ()->$attrName = $value;
          $component->removeBinding ($attrName);
        }
      }
    if ($component->hasChildren ())
      foreach ($component->getChildren () as $child)
        self::parsingtimeDatabind ($child, $instance, $force || $component instanceof ContentProperty);
  }

  public function apply (MacroInstance $instance)
  {
    $o      = [];
    $styles = $this->props ()->style;
    if (isset($styles))
      foreach ($styles as $sheet) {
        self::parsingtimeDatabind ($sheet, $instance);
        if (isset($sheet->props ()->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->props ()->src,
          ];
        else if (!empty($sheet->getChildren()))
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->props ()->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->props ()->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        self::parsingtimeDatabind ($script, $instance);
        if (isset($script->props ()->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->props ()->src,
          ];
        else if (!empty($script->getChildren()))
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->props ()->get ('name'),
            'defer' => $script->props ()->get ('defer'),
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

//    $styles = $this->props ()->get ('style');
//    if (isset($styles))
//      foreach ($styles as $sheet) {
//        self::parsingtimeDatabind ($sheet, $instance);
//        if (isset($sheet->props ()->src))
//          $instance->page->addStylesheet ($sheet->props ()->src);
//        else if (!empty($sheet->children)) {
//          $name = $sheet->props ()->get ('name');
//          $instance->page->addInlineCss ($sheet, $name);
//        }
//      }
//    $scripts = $this->props ()->get ('script');
//    if (isset($scripts)) {
//      foreach ($scripts as $script) {
//        self::parsingtimeDatabind ($script, $instance);
//        if (isset($script->props ()->src))
//          $instance->page->addScript ($script->props ()->src);
//        else if (!empty($script->children)) {
//          $name = $script->props ()->get ('name');
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
                      $component->props ()->$field = $value;
                      $component->removeBinding ($field);
                    }
                  }
                  else {
                    //simple exp. (binding ref. only}
                    $attrName = $match[1];
                    if (!$instance->props ()->defines ($attrName)) {
                      $s = join (', ', $instance->props ()->getAttributeNames ());
                      throw new ComponentException($instance,
                        "<p>The parameter <b>$attrName</b>, specified on a call to/in the <b>{$this->props ()->name}</b> macro, is not defined on that macro.</p>
<table>
  <th>Expected parameters:<td>$s
  <tr><th>Instance:<td>{$instance->getTagName ()}
</table>");
                    }
                    if (isset($this->bindings) && array_key_exists ($attrName, $this->bindings))
                      $content = $this->bindings[$attrName];
                    else $content = $instance->props ()->$attrName;
                    if (isset($instance->bindings) &&
                        array_key_exists ($attrName, $instance->bindings)
                    ) {
                      //transfer binding from the macro instance to the component
                      $component->addBinding ($field, $instance->bindings[$attrName]);
                      continue;
                    }
                    $value = $content instanceof ContentProperty ? $content->getValue () : $content;
                    if ($component instanceof Literal) {
                      if (is_array ($value)) {
                        //replace literal by a component set
                        array_splice ($components, $i, 1, $value);
                        $i += count ($value) - 1;
                        continue;
                      }
                      if (!self::isBindingExpression ($value))
                        //convert boolean value to string, only for literals
                        if ($instance->props ()->getTypeOf ($attrName) == type::bool)
                          $value =
                            ComponentProperties::validateScalar (type::bool, $value)
                              ? 'true' : 'false';
                    }
                    if (self::isBindingExpression ($value)) {
                      //assign new binding expression to target component
                      $component->addBinding ($field, $value);
                    }
                    else {
                      $component->props ()->$field = $value;
                      $component->removeBinding ($field);
                    }
                  }
                }
              }
            }
          }
          $attrs  = $component->props ()->getAttributesOfType (type::content);
          $values = array_values ($attrs);
          $this->applyTo ($values, $instance);
          $attrs  = $component->props ()->getAttributesOfType (type::collection);
          $values = array_values ($attrs);
          foreach ($values as $paramArray)
            $this->applyTo ($paramArray, $instance);
          $this->applyTo ($component->getChildrenRef (), $instance);
        }
      }
  }

  /**
   * @return MacroProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Returns the macro parameter with the given name.
   * @param string $name
   * @return ContentProperty
   */
  public function getParameter ($name)
  {
    $name   = denormalizeAttributeName ($name);
    $params = $this->props ()->get ('param');
    if (!is_null ($params))
      foreach ($params as $param)
        if ($param->props ()->name == $name)
          return $param;

    return null;
  }

  public function getParameterType ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $p = type::getIdOf($param->props ()->type);
      if ($p === false) {
        $s = join ('</b>, <b>', array_slice (type::NAMES, 1));
        throw new ComponentException($this,
          "The type attribute for the <b>$name</b> parameter is invalid.\nExpected values: <b>$s</b>.");
      }

      return $p;
    }

    return null;
  }

  public function getParametersNames ()
  {
    $params = $this->props ()->get ('param');
    if (is_null ($params)) return null;
    $names = [];
    foreach ($params as $param)
      $names[] = $param->props ()->name;

    return $names;
  }

  /**
   * @return MacroProperties
   */
  public function newProperties ()
  {
    return new MacroProperties($this);
  }

  public function parsed ()
  {
    $this->context->addMacro ($this->props ()->name, $this);
  }
}
