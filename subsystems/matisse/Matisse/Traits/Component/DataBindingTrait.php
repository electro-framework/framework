<?php
namespace Selenia\Matisse\Traits\Component;

use PhpCode;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\HandlerNotFoundException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Parser;
use Selenia\Matisse\Properties\Base\ComponentProperties;

/**
 * Provides an API for handling data binding on a component's properties.
 *
 * It's applicable to the Component class.
 *
 * @property Context             $context  The rendering context.
 * @property ComponentProperties $props    The component's attributes.
 * @property Component           $parent   The component's parent.
 */
trait DataBindingTrait
{
  /**
   * Finds binding expressions and extracts information from them.
   * > Note: the u modifier allows unicode white space to be properly matched.
   */
  static private $PARSE_BINDING_EXP = '#
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
   * A map of attribute names and corresponding databinding expressions.
   * Equals NULL if no bindings are defined.
   *
   * @var array
   */
  public $bindings = null;
  /**
   * The component's own view model.
   * <p>Do not confuse this with {@see Context::viewModel}, the later will be effective only if a field is not found on
   * any of the cascaded component view models.
   *
   * @var mixed
   */
  public $viewModel;

  static function isCompositeBinding ($exp)
  {
    return $exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '{{', 2) > 0 || strpos ($exp, '{!!', 2) > 0;
  }

  /**
   * Registers a data binding.
   *
   * @param string $prop    The name of the bound attribute.
   * @param string $bindExp The binding expression.
   */
  public final function addBinding ($prop, $bindExp)
  {
    if (!isset($this->bindings))
      $this->bindings = [];
    $this->bindings[$prop] = $bindExp;
  }

  public final function isBound ($prop)
  {
    return isset($this->bindings) && array_key_exists ($prop, $this->bindings);
  }

  public final function removeBinding ($prop)
  {
    if (isset($this->bindings)) {
      unset($this->bindings[$prop]);
      if (empty($this->bindings))
        $this->bindings = null;
    }
  }

  /**
   * Gets a field from the current data-binding context.
   * > This is reserved for internal use by compiled data-binding expressions.
   *
   * @param string $field
   * @return mixed
   * @throws DataBindingException
   */
  protected function _f ($field)
  {
    if (isset($this->viewModel)) {
      $data = $this->viewModel;
      if (is_array ($data)) {
        if (array_key_exists ($field, $data))
          return $data[$field];
      }
      else if (is_object ($data)) {
        if (property_exists ($data, $field))
          return $data->$field;
      }
      else $this->throwInvalidData ($data, $field);
    }

    /** @var static $parent */
    $parent = $this->parent;
    if (isset($parent))
      return $parent->_f ($field);

    $data = $this->context->viewModel;
    if (isset($data)) {
      if (is_array ($data)) {
        if (array_key_exists ($field, $data))
          return $data[$field];
      }
      else if (is_object ($data)) {
        if (property_exists ($data, $field))
          return $data->$field;
      }
      else $this->throwInvalidData ($data, $field);
    }
    return null;
  }

  protected function bindToAttribute ($name, $value)
  {
    if (is_object ($value))
      $this->props->$name = $value;
    else $this->props->set ($name, $value);
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
          $bindExp = preg_replace_callback (self::$PARSE_BINDING_EXP, [$this, 'evalBindingExp'], $bindExp);
          if (!Parser::isBindingExpression ($bindExp))
            return $bindExp;
        }
        else {
          //simple expression
          preg_match (self::$PARSE_BINDING_EXP, $bindExp, $matches);
          $bindExp = $this->evalBindingExp ($matches);
          if (!Parser::isBindingExpression ($bindExp))
            return $bindExp;
        }
        if (++$z > 10)
          throw new DataBindingException($this,
            "The maximum nesting depth for a data binding expression was exceeded.<p>The last evaluated expression is   <b>$bindExp</b>");
      } while (true);
    }
    catch (\InvalidArgumentException $e) {
      throw new DataBindingException($this, "Invalid databinding expression: $bindExp\n" . $e->getMessage (), $e);
    }
  }

  /**
   * Returns the current value of an attribute, performing databinding if necessary.
   *
   * <p>This is only required on situation where you need a property's value before databinging has occured.
   *
   * @param string $name
   * @return mixed
   * @throws DataBindingException
   */
  protected function evalProp ($name)
  {
    if (isset($this->bindings[$name]))
      return $this->evalBinding ($this->bindings[$name]);

    return $this->props->get ($name);
  }

  protected function parseIteratorExp ($exp, & $idxVar, & $itVar)
  {
    if (!preg_match ('/^(?:(\w+):)?(\w+)$/', $exp, $m))
      throw new ComponentException($this,
        "Invalid value for attribute <kbd>as</kbd>.<p>Expected syntax: <kbd>'var'</kbd> or <kbd>'index:var'</kbd>");
    list (, $idxVar, $itVar) = $m;
  }

  private function compileExpression ($expression)
  {
    if ($expression[0] == '#') {
      $exp = substr ($expression, 1);
      return function () use ($exp) {
        $block = $this->context->getBlock ($exp);
        /** @var Component $this */
        return $this->attachSetAndGetContent ($block);
      };
    }
    $exp = PA (preg_split ('/ (?= \|\| | && | \+ ) /xu', $expression))
      ->map (function ($x) { return trim ($x); })
      ->map (function ($x) {
        if (str_beginsWith ($x, '||') || str_beginsWith ($x, '&&'))
          return substr ($x, 0, 2) . $this->compileSubexpression (trim (substr ($x, 2)));
        if (str_beginsWith ($x, '+'))
          return '.' . $this->compileSubexpression (trim (substr ($x, 1)));
        return $this->compileSubexpression ($x);
      })->join ();
    return PhpCode::compile ($exp);
  }

  private function compileSubexpression ($expression)
  {
    {
      $exp  = $not = '';
      $segs = explode ('.', $expression);
      foreach ($segs as $i => $seg)
        if ($i)
          $exp = "_g($exp,'$seg')";
        else {
          if ($seg[0] == '!') {
            $not = '!';
            $seg = substr ($seg, 1);
          }
          $exp = $seg[0] == '"' ? $seg : "\$this->_f('$seg')";
        }
      $exp = "$not$exp";
      if (!PhpCode::validateExpression ($exp))
        throw new DataBindingException($this, "Invalid expression <kbd>$expression</kbd>.");

      return $exp;
    }

  }

  private function evalBindingExp ($matches)
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

    $pipes      = preg_split ('/\s*(?<!\|)\|(?!\|)\s*/', $expression);
    $expression = array_shift ($pipes);
    $v          = null;
    $rawOutput  = $openDelim == '{!!';

    // Call a previously compiled expression or compile one now, cache it and call it.

    /** @var \Closure $compiled */
    $compiled = get (Context::$expressions, $expression);
    if (!$compiled)
      $compiled = Context::$expressions[$expression] = $this->compileExpression ($expression);
    // Compatible with PHP < 7
    $c = \Closure::bind ($compiled, $this, $this);
    $v = $c ();

    // Apply pipes to expression result

    foreach ($pipes as $name) {
      $name = trim ($name);

      // Parse pipe expression, with syntax: pipe(args1,...argN)

      if (substr ($name, -1) == ')') {
        list ($name, $args) = explode ('(', substr ($name, 0, -1));
        $name   = trim ($name);
        $args   = explode (',', $args);
        $fnArgs = array_from ($v, ...$args);
      }
      else $fnArgs = [$v];

      $pipe = $this->context->getPipe ($name);
      try {
        $v = call_user_func_array ($pipe, $fnArgs);
      }
      catch (HandlerNotFoundException $e) {
        throw new ComponentException ($this, "Pipe <b>$name</b> was not found.");
      }
    }

    // Return the computed value

    return $rawOutput || !is_scalar ($v) ? $v : e ($v);
  }

  private function throwInvalidData ($data, $field)
  {
    throw new DataBindingException ($this,
      "Can't get property <kbd>$field</kbd> from a value of type <kbd>" . gettype ($data) . "</kbd>");
  }

}
