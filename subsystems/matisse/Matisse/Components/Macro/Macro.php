<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Parser;
use Selenia\Matisse\Properties\Macro\MacroProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * The Macro component allows you to define a macro trasformation via markup.
 *
 * <p>A macro is composed by metadata elements and a template.
 * - With metadata you can define macro parameters, stylesheets and scripts.
 * - All child elements that are not metadata define the template that will be transformed and replace a
 * {@see MacroInstance} that refers to the Macro.
 *
 * > A `MacroInstance` is a component that can be represented via any tag that has the same name as the macro it refers
 * to.
 */
class Macro extends Component
{
  /** Finds binding expressions which are not macro parameter bindings. */
  const FIND_NON_MACRO_EXP = '#\{\{\s*(?=\S)[^@]#u';
  /** Finds macro binding expressions. */
  const PARSE_MACRO_BINDING_EXP = '#\{\{\s*@([\w\-]*)\s*([|.][^\}]*)?\s*\}\}#u';

  protected static $propertiesClass = MacroProperties::class;

  public $allowsChildren = true;
  /** @var MacroProperties */
  public $props;

  private static function evalScalarExp ($bindExp, MacroInstance $instance, &$transfer_binding = null)
  {
    $transfer_binding = false;
    return preg_replace_callback (self::PARSE_MACRO_BINDING_EXP,
      function ($args) use ($instance, $transfer_binding) {
        return self::evalScalarRef ($args[1], $instance, $transfer_binding);
      }, $bindExp);
  }

  private static function evalScalarRef ($ref, MacroInstance $instance, &$transfer_binding)
  {
    if (isset($instance->bindings) && array_key_exists ($ref, $instance->bindings)) {
      $transfer_binding = true;

      return $instance->bindings[$ref];
    }
    $value = $instance->props->$ref;
    if (Parser::isBindingExpression ($value))
      return $value;
    if (is_null ($value) || $value === '')
      $value = $instance->props->getDefault ($ref);

    return $value;
  }

  private static function parsingtimeDatabind (Component $component, MacroInstance $instance, $force = false)
  {
    if (isset($component->bindings))
      foreach ($component->bindings as $attrName => $bindExp) {
        $value            = self::evalScalarExp ($bindExp, $instance);
        $transfer_binding = Parser::isBindingExpression ($value);
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
          $component->props->$attrName = $value;
          $component->removeBinding ($attrName);
        }
      }
    if ($component->hasChildren ())
      foreach ($component->getChildren () as $child)
        self::parsingtimeDatabind ($child, $instance, $force || $component instanceof Metadata);
  }

  public function apply (MacroInstance $instance)
  {
    $params = $this->getParametersNames ();
    if (in_array ('macro', $params))
      throw new ComponentException($this, "The parameter name <b>macro</b> is reserved, you can't use it");

    $o      = [];
    $styles = $this->props->style;
    if (isset($styles))
      foreach ($styles as $sheet) {
        self::parsingtimeDatabind ($sheet, $instance);
        if (isset($sheet->props->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->props->src,
          ];
        else if (!empty($sheet->getChildren ()))
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->props->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->props->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        self::parsingtimeDatabind ($script, $instance);
        if (isset($script->props->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->props->src,
          ];
        else if (!empty($script->getChildren ()))
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->props->get ('name'),
            'data'  => $script,
          ];
      }
    }
    //$o = array_reverse ($o);
    foreach ($o as $i)
      switch ($i['type']) {
        case 'sh':
          $instance->context->addStylesheet ($i['src'], false);
          break;
        case 'ish':
          $instance->context->addInlineCss ($i['data'], $i['name'], false);
          break;
        case 'sc':
          $instance->context->addScript ($i['src'], false);
          break;
        case 'isc':
          $instance->context->addInlineScript ($i['data'], $i['name'], false);
          break;
      }

    $cloned = $this->getClonedChildren ();
    $this->applyTo ($cloned, $instance);

    return $cloned;
  }

  public function applyTo (array &$components = null, MacroInstance $instance)
  {
    if (!is_null ($components))
      for ($i = 0; $i < count ($components); ++$i) {
        /** @var Component $component */
        $component = $components[$i];
        if (!is_null ($component)) {
          if (isset($component->bindings)) {
            foreach ($component->bindings as $field => $exp)
              $this->applyBinding ($component, $field, $exp, $instance, $components, $i);
          }
          $props  = $component->props->getPropertiesOf (type::metadata);
          $values = array_values ($props);
          $this->applyTo ($values, $instance);

          $props  = $component->props->getPropertiesOf (type::content);
          $values = array_values ($props);
          $this->applyTo ($values, $instance);

          $props  = $component->props->getPropertiesOf (type::collection);
          $values = array_values ($props);
          foreach ($values as $paramArray)
            $this->applyTo ($paramArray, $instance);

          $this->applyTo ($component->getChildrenRef (), $instance);
        }
      }
  }

  /**
   * Returns the macro parameter with the given name.
   *
   * @param string $name
   * @return Metadata|null null if not found.
   */
  public function getParameter ($name)
  {
    $params = $this->props->get ('param');
    if (!is_null ($params))
      foreach ($params as $param)
        if ($param->props->name == $name)
          return $param;

    return null;
  }

  /**
   * Gets a parameter's enumeration (if any).
   *
   * @param string $name Parameter name.
   * @return array|null null if no enumeration is defined.
   */
  public function getParameterEnum ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $enum = $param->props->get ('enum');
      if (exists ($enum))
        return explode (',', $enum);
    }
    return null;
  }

  public function getParameterType ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $p = type::getIdOf ($param->props->type);
      if ($p === false) {
        $s = join ('</kbd>, <kbd>', type::getAllNames ());
        throw new ComponentException($this,
          "The <kbd>$name</kbd> parameter has an invalid type: <kbd>{$param->props->type}</kbd>.<p>Expected values: <kbd>$s</kbd>.");
      }
      return $p;
    }
    return null;
  }

  public function getParametersNames ()
  {
    $params = $this->props->get ('param');
    if (is_null ($params)) return null;
    $names = [];
    foreach ($params as $param)
      $names[] = lcfirst ($param->props->name);

    return $names;
  }

  public function onParsingComplete ()
  {
    $this->context->addMacro ($this->props->name, $this);
  }

  private function applyBinding (Component $component, $field, $exp, MacroInstance $instance, array & $components, & $i)
  {
    if (preg_match (self::PARSE_MACRO_BINDING_EXP, $exp, $match)) {
      //evaluate macro binding expression
      if (preg_match (self::FIND_NON_MACRO_EXP, $exp)) {
        //mixed (data/macro) binding
        $component->addBinding ($field,
          self::evalScalarExp ($exp, $instance)); //replace current binding
      }
      else {
        if ($exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '}') < strlen ($exp) - 2
        ) {
          // Composite exp. (constant text + binding ref)

          $value = self::evalScalarExp ($exp, $instance, $transfer_binding);
          if ($transfer_binding)
            $component->addBinding ($field, $value); //replace current binding
          else {
            //final value is not a binding exp.
            $component->props->$field = $value;
            $component->removeBinding ($field);
          }
        }
        else {
          // Simple exp. (binding ref. only}

          $prop = normalizeAttributeName ($match[1]);
          if (!$instance->props->defines ($prop)) {
            $s = join (', ', $instance->props->getPropertyNames ());
            throw new ComponentException($instance,
              "<p>The macro parameter <b>$prop</b>, specified on a call to the <b>{$this->props->name}</b> macro, is not defined on that macro.</p>
<table>
  <th>Expected parameters:<td>$s
  <tr><th>Instance:<td>{$instance->getTagName ()}
</table>");
          }
          if (isset($this->bindings) && array_key_exists ($prop, $this->bindings))
            $content = $this->bindings[$prop];
          else $content = $instance->props->$prop;

          if (isset($instance->bindings) && array_key_exists ($prop, $instance->bindings)) {

            // Transfer binding from the macro instance to the component.

            $newBinding = $instance->bindings[$prop];
            // Transfer remaining original expression, if any.
            $pipe = get($match, 2);
            if (isset($pipe))
              $newBinding = rtrim(substr($newBinding,0,-2)) . $pipe . substr($newBinding, -2);

            $component->addBinding ($field, $newBinding);
            return;
          }

          $value = $content instanceof Metadata ? $content->getValue () : $content;

          if ($component instanceof Literal) {
            if (is_array ($value)) {

              // Replace literal by a component set.

              array_splice ($components, $i, 1, $value);
              $i += count ($value) - 1;
              return;
            }
//            if (!Parser::isBindingExpression ($value))
//
//              // Convert boolean value to string, only for literals.
//
//              if ($instance->props->getTypeOf ($attrName) == type::bool)
//                $value = $this->props->typecastPropertyValue (type::bool, $value)
//                  ? 'true' : 'false';
          }

          if (Parser::isBindingExpression ($value)) {

            // Assign new binding expression to target component.

            $component->addBinding ($field, $value);
          }
          else {
            $component->props->set ($field, $value);
            $component->removeBinding ($field);
          }
        }
      }
    }
  }

}
