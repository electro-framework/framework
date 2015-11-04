<?php
namespace Selenia\Matisse\Traits;

use PhpCode;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Context;
use Selenia\Matisse\DataSource;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\HandlerNotFoundException;
use Selenia\Matisse\MatisseEngine;

/**
 * Provides an API for handling data binding on a component's properties.
 *
 * It's applicable to the Component class.
 *
 * @property Context             $context  The rendering context.
 * @property ComponentAttributes $attrsObj The component's attributes.
 */
trait DataBindingTrait
{
  /**
   * Finds binding expressions and extracts information from them.
   * > Note: the u modifier allows unicode white space to be properly matched.
   */
  static private $PARSE_PARAM_BINDING_EXP = '#
    ( \{ (?: \{ | !! ))
    \s*
    (
      (?:
        (?! \s*\} | \s*!! )
        .
      )*
    )
    \s*
    ( \}\} | !!\} )
  #xu';
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
  public $contextualModel;

  static function isBindingExpression ($exp)
  {
    return is_string ($exp) ? strpos ($exp, '{{') !== false || strpos ($exp, '{!!') !== false : false;
  }

  static function isCompositeBinding ($exp)
  {
    return $exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '{{', 2) > 0 || strpos ($exp, '{!!', 2) > 0;
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

  function getCascaded ($field)
  {
    if (isset($this->contextualModel)) {
      $data = $this->contextualModel;
      if (is_array ($data)) {
        if (array_key_exists ($field, $data))
          return $data[$field];
      }
      else if (is_object ($data)) {
        if (property_exists ($data, $field))
          return $data->$field;
      }
    }
    /** @var static $parent */
    $parent = $this->parent;
    return isset($parent) ? $parent->getCascaded ($field) : null;
  }

  function getField ($field)
  {
    $data = $this->context->viewModel;
    if (is_array ($data)) {
      if (array_key_exists ($field, $data))
        return $data[$field];
      return $this->getCascaded ($field);
    }
    else if (is_object ($data)) {
      if (property_exists ($data, $field))
        return $data->$field;
      return $this->getCascaded ($field);
    }
    else throw new DataBindingException ($this,
      "Can't get property <kbd>$field</kbd> from a value of type <kbd>" . gettype ($data) . "</kbd>");
  }

  public final function isBound ($fieldName)
  {
    return isset($this->bindings) && array_key_exists ($fieldName, $this->bindings);
  }

  public final function removeBinding ($attrName)
  {
    if (isset($this->bindings)) {
      unset($this->bindings[$attrName]);
      if (empty($this->bindings))
        $this->bindings = null;
    }
  }

  protected function bindToAttribute ($name, $value)
  {
    if (is_object ($value))
      $this->attrsObj->$name = $value;
    else $this->attrsObj->set ($name, $value);
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
          $bindExp = preg_replace_callback (self::$PARSE_PARAM_BINDING_EXP, [$this, 'evalBindingExp'], $bindExp);
          if (!self::isBindingExpression ($bindExp))
            return $bindExp;
        }
        else {
          //simple expression
          preg_match (self::$PARSE_PARAM_BINDING_EXP, $bindExp, $matches);
          $bindExp = $this->evalBindingExp ($matches);
          if (!self::isBindingExpression ($bindExp))
            return $bindExp;
        }
        if (++$z > 10)
          throw new DataBindingException($this,
            "The maximum nesting depth for a data binding expression was exceeded.<p>The last evaluated expression is   <b>$bindExp</b>");
      } while (true);
    } catch (\InvalidArgumentException $e) {
      throw new DataBindingException($this, "Invalid databinding expression: $bindExp\n" . $e->getMessage (), $e);
    }
  }

  protected function evalBindingExp ($matches)
  {
    if (empty($matches))
      throw new \InvalidArgumentException;
    list($full, $openDelim, $expression, $closeDelim) = $matches;
    if ($openDelim == '{{' && $closeDelim != '}}' || $openDelim == '{!!' && $closeDelim != '!!}')
      throw new DataBindingException($this,
        "Invalid databinding expression: <kbd>$full</kbd><p>Closing delimiter does not match the open delimiter.");

    if ($expression == '')
      return null;

    // Parse pipes

    $pipes      = preg_split ('/\s*\|\s*/', $expression);
    $expression = array_shift ($pipes);
    $v          = null;
    $rawOutput  = $openDelim == '{!!';

    // Call a previously compiled expression or compile one now, cache it and call it.

    /** @var \Closure $compiled */
    $compiled = get (MatisseEngine::$expressions, $expression);
    if (!$compiled) {
      $args = explode ('.', $expression);
      $exp  = '';
      foreach ($args as $i => $arg)
        if ($i)
          $exp = "_g($exp,'$arg')";
        else $exp = "\$this->getField('$arg')";
      if (!PhpCode::validateExpression ($exp))
        throw new DataBindingException($this, "Invalid expression <kbd>$expression</kbd>.");
      $compiled = MatisseEngine::$expressions[$expression] = PhpCode::compile ($exp);
    }
    $v = $compiled->call ($this);

    // Apply pipes to expression result

    foreach ($pipes as $name) {
      $pipe = $this->context->getPipe (trim ($name));
      try {
        $v = call_user_func ($pipe, $v, $this->context);
      } catch (HandlerNotFoundException $e) {
        throw new ComponentException ($this, "Pipe <b>$name</b> was not found.");
      }
    }

    // Return the computed value

    return $rawOutput || !is_scalar ($v) ? $v : e ($v);
  }

  /**
   * Returns the current value of an attribute, performing databinding if necessary.
   * @param string $name
   * @return mixed
   * @throws DataBindingException
   */
  protected function evaluateAttr ($name)
  {
    if (isset($this->bindings[$name]))
      return $this->evalBinding ($this->bindings[$name]);

    return $this->attrsObj->$name;
  }

  /**
   * Returns the data source to be used for #model contextual databinging expressions.
   * Searches upwards on the component hierarchy.
   *
   * @return DataSource
   */
  protected function getContextualModel ()
  {
    if (isset($this->contextualModel))
      return $this->contextualModel;
    /** @var static $parent */
    $parent = $this->parent;
    return isset($parent) ? $parent->getContextualModel () : null;
  }

  protected function parseIteratorExp ($exp, & $idxVar, & $itVar)
  {
    if (!preg_match ('/^(?:(\w+):)?(\w+)$/', $exp, $m))
      throw new ComponentException($this,
        "Invalid value for attribute <kbd>as</kbd>.<p>Expected syntax: <kbd>'var'</kbd> or <kbd>'index:var'</kbd>");
    list (, $idxVar, $itVar) = $m;
  }

}
