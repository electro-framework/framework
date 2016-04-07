<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\MacroExtensionInterface;
use Selenia\Matisse\Parser\Expression;
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
  const FIND_NON_MACRO_EXP = '#\{\s*(?=\S)[^@]#u';
  /** Finds macro binding expressions. */
  const PARSE_MACRO_BINDING_EXP = '#\{\s*@([\w\-]*)\s*([|.][^\}]*)?\s*\}#u';

  protected static $propertiesClass = MacroProperties::class;

  public $allowsChildren = true;
  /** @var MacroProperties */
  public $props;

  private static function evalScalarExp ($bindExp, MacroCall $instance, &$transfer_binding = null)
  {
    $transfer_binding = false;
    return preg_replace_callback (self::PARSE_MACRO_BINDING_EXP,
      function ($args) use ($instance, $transfer_binding) {
        return self::evalScalarRef ($args[1], get ($args, 2), $instance, $transfer_binding);
      }, $bindExp);
  }

  private static function evalScalarRef ($ref, $extra, MacroCall $instance, &$transfer_binding)
  {
    if (isset($instance->bindings) && array_key_exists ($ref, $instance->bindings)) {
      $transfer_binding = true;
      $exp              = $instance->bindings[$ref];
      // Insert $extra after the evaluated value
      preg_match ('/[\s}!]+$/', $exp, $m);
      $p = strlen ($m[0]);
      return $extra !== '' ? substr_replace ($exp, $extra, -$p, 0) : $exp;
    }
    $value = $instance->props->$ref;
    if (Expression::isBindingExpression ($value))
      return $value;
    if (is_null ($value) || $value === '')
      $value = $instance->props->getDefault ($ref);

    return $value;
  }

  private static function parsingtimeDatabind (Component $component, MacroCall $instance, $force = false)
  {
    if (isset($component->bindings))
      foreach ($component->bindings as $attrName => $bindExp) {
        // Skip non-macro expressions.
        if (!preg_match (self::PARSE_MACRO_BINDING_EXP, $bindExp))
          continue;

        $value = self::evalScalarExp ($bindExp, $instance);
        // Check if the evaluated result is itself a binding expression.
        $transfer_binding = Expression::isBindingExpression ($value);
        // If it is, update the binding on the component.
        if ($transfer_binding) {
//          if ($force) {
//            $value = $component->evalBinding($value);
//            $component->props()->$attrName = $value;
//            $component->removeBinding ($attrName);
//          }
          $component->addBinding ($attrName, $value); //replace current binding
        }
        else {
          // Otherwise, remove the binding from the component, as it already evaluated as a constant value.
          $component->props->$attrName = $value;
          $component->removeBinding ($attrName);
        }
      }
    if ($component->hasChildren ())
      foreach ($component->getChildren () as $child)
        self::parsingtimeDatabind ($child, $instance, $force /*|| $component instanceof Metadata*/);
  }

  public function apply (MacroCall $instance)
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
            'type' => 'isc',
            'name' => $script->props->get ('name'),
            'data' => $script,
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

  public function applyTo (array &$components = null, MacroCall $call)
  {
    if (!is_null ($components))
      for ($i = 0; $i < count ($components); ++$i) {
        /** @var Component $component */
        $component = $components[$i];

        if (isset ($component) && isset($component->props)) {
          if (isset($component->bindings)) {
            foreach ($component->bindings as $field => $exp)
              if ($this->applyBinding ($component, $field, $exp, $call, $components, $i))
                continue 2;
          }
          $props  = $component->props->getPropertiesOf (type::metadata);
          $values = array_values ($props);
          $this->applyTo ($values, $call);

          $props  = $component->props->getPropertiesOf (type::content);
          $values = array_values ($props);
          $this->applyTo ($values, $call);

          $props  = $component->props->getPropertiesOf (type::collection);
          $values = array_values ($props);
          foreach ($values as $paramArray)
            $this->applyTo ($paramArray, $call);

          /** @var MacroExtensionInterface|Component $component */
          if ($component instanceof MacroExtensionInterface) {
            if (!$component->onMacroApply ($this, $call, $components, $i))
              continue;
          }
          $this->applyTo ($component->getChildrenRef (), $call);
        }
      }
  }

  /**
   * Returns the macro parameter with the given name.
   *
   * @param string $name
   * @return Metadata|null null if not found.
   */
  public function getParameter ($name, &$found = false)
  {
    $params = $this->props->get ('param');
    if (!is_null ($params))
      foreach ($params as $param)
        if ($param->props->name == $name) {
          $found = true;
          return $param;
        }

    $found = false;
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
    $this->props->name = normalizeTagName ($this->props->name);
    $this->context->addMacro ($this);
  }

  private function applyBinding (Component $component, $field, Expression $expression, MacroCall $instance,
                                 array & $components, & $i)
  {
    $exp = (string)$expression;
    if (preg_match (self::PARSE_MACRO_BINDING_EXP, $exp, $match)) {
      //evaluate macro binding expression
      if (preg_match (self::FIND_NON_MACRO_EXP, $exp)) {
        //mixed (data/macro) binding
        $component->addBinding ($field,
          self::evalScalarExp ($exp, $instance)); //replace current binding
      }
      else {
        if (Expression::isCompositeBinding ($exp)) {
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
            $filter = get ($match, 2);
            if (isset($filter))
              $newBinding = new Expression (rtrim (substr ($newBinding, 0, -1)) . $filter . substr ($newBinding, -1));

            $component->addBinding ($field, $newBinding);
            return;
          }

          $value = $content instanceof Metadata ? $content->getValue () : $content;

          if (Expression::isBindingExpression ($value)) {

            // Assign new binding expression to target component.

            $component->addBinding ($field, $value);
          }
          else {
            if (is_array ($value)) {
              // Replace current component by the macro substitution's set of components.
              array_splice ($components, $i, 1, $value);
              --$i;
              return true;
            }
            else {
              // Update the property value to the result of the macro param.
              $component->props->set ($field, $value);
              $component->removeBinding ($field);
            }
          }
        }
      }
    }
  }

}
