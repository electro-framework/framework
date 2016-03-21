<?php
namespace Selenia\Matisse\Traits\Component;

use PhpCode;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;
use Selenia\Matisse\Parser\Context;
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
  static private $PARSE_BINDING_EXP = '/
    \{              # opens with {
    \s*
    (
      (?:           # repeat
        (?! \s*\})  # not space followed by a }
        .
      )*
    )
    \s*
    \}              # closes with }
  /xu';
  /**
   * A map of attribute names and corresponding databinding expressions.
   * Equals NULL if no bindings are defined.
   *
   * > <p>It has `public` visibility so that it can be inspected externally.
   *
   * @var array
   */
  public $bindings = null;
  /**
   * When set, the component's view model is made available on the shared view model under the specified key name.
   *
   * @var string
   */
  protected $shareViewModelAs = null;
  /**
   * The component's own view model.
   * <p>Do not confuse this with {@see Context::viewModel}, the later will be effective only if a field is not found on
   * any of the cascaded component view models.
   *
   * @var mixed
   */
  protected $viewModel;

  static function isCompositeBinding ($exp)
  {
    return $exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '{', 1) > 0;
  }

  /**
   * Registers a data binding.
   *
   * @param string $prop    The name of the bound attribute.
   * @param string $bindExp The binding expression.
   */
  public function addBinding ($prop, $bindExp)
  {
    if (!isset($this->bindings))
      $this->bindings = [];
    $this->bindings[$prop] = $bindExp;
  }

  /**
   * Evaluates the given binding expression without compiling it.
   *
   * <p>Only simple expressions are supported, i.e. without operators or filters.
   *
   * @param string $exp A binding expression, without enclosing brackets.
   * @return mixed
   */
  function evalSimpleExp ($exp)
  {
    $v = PhpCode::evalConstant ($exp, $ok);
    if ($ok)
      return $v;
    $o = explode ('.', $exp);
    $t = $this;
    foreach ($o as $seg) {
      $t = $t->_f ($seg);
    }
    return $t;
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
  public function getComputedPropValue ($name)
  {
    if (isset($this->bindings[$name]))
      return $this->evalBindingExpression ($this->bindings[$name]);

    return $this->props->get ($name);
  }

  public function isBound ($prop)
  {
    return isset($this->bindings) && array_key_exists ($prop, $this->bindings);
  }

  public function removeBinding ($prop)
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
    $data = $this->viewModel;
    if (isset($data)) {
      $v = _g ($data, $field, $this);
      if ($v !== $this)
        return $v;
    }

    /** @var static $parent */
    $parent = $this->parent;
    if (isset($parent))
      return $parent->_f ($field);

    $data = $this->context->viewModel;
    if (isset($data)) {
      $v = _g ($data, $field, $this);
      if ($v !== $this)
        return $v;
    }

    return null;
  }

  protected function databind ()
  {
    if (isset($this->bindings))
      foreach ($this->bindings as $attrName => $bindExp) {
        $value = $this->evalBindingExpression ($bindExp);
        if (is_object ($value))
          $this->props->$attrName = $value;
        else $this->props->set ($attrName, $value);
      }
  }

  protected function evalBindingExpression ($bindExp)
  {
    if (!is_string ($bindExp))
      return $bindExp;
    try {
      if (self::isCompositeBinding ($bindExp))
        //composite expression
        return preg_replace_callback (self::$PARSE_BINDING_EXP, [$this, 'evalBindingExp'], $bindExp);

      //simple expression
      preg_match (self::$PARSE_BINDING_EXP, $bindExp, $matches);
      return $this->evalBindingExp ($matches);
    }
    catch (\InvalidArgumentException $e) {
      throw new DataBindingException($this, "Invalid databinding expression: $bindExp\n" . $e->getMessage (), $e);
    }
  }

  protected function parseIteratorExp ($exp, & $idxVar, & $itVar)
  {
    if (!preg_match ('/^(?:(\w+):)?(\w+)$/', $exp, $m))
      throw new ComponentException($this,
        "Invalid value for attribute <kbd>as</kbd>.<p>Expected syntax: <kbd>'var'</kbd> or <kbd>'index:var'</kbd>");
    list (, $idxVar, $itVar) = $m;
  }

  /**
   * Compiles a databinding expression.
   *
   * <p>Valid expression syntaxes:
   *   - `x.y.z`
   *   - `!a && b || c`
   *   - `a + b + 'c' + "d" + 123`
   *   - `#block`
   *   - `@`prop
   *
   * <p>Valid constants:
   *   - 123 or 123.4
   *   - "string" or 'string'
   *   - true, false, null
   *   - any PHP constant defined via `define()` or `const`
   *   - namespace\class::constant (`self` or `static` or not valid)
   *
   * @param $expression
   * @return \Closure
   */
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
      ->map (function ($x) {
        $x = trim ($x);
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
    if ($expression[0] == '@')
      $expression = 'props.' . substr ($expression, 1);
    $exp  = $not = '';
    $segs = explode ('.', $expression);
    foreach ($segs as $i => $seg) {
      if ($i)
        $exp = "_g($exp,'$seg')";
      else {
        if ($seg[0] == '!') {
          $not = '!';
          $seg = substr ($seg, 1);
        }
        // If not a constant value, convert it to a property access expression fragment.
        $exp = $seg[0] == '"' || $seg[0] == "'" || ctype_digit ($seg)
          ? $seg
          : "\$this->_f('$seg')";
      }
    }
    $exp = "$not$exp";
    if (!PhpCode::validateExpression ($exp))
      throw new DataBindingException($this, "Invalid expression <kbd>$expression</kbd>.");
    return $exp;
  }

  private function evalBindingExp ($matches)
  {
    if (empty($matches))
      throw new \InvalidArgumentException;
    list($full, $expression) = $matches;
    if ($expression == '')
      return null;

    // Parse filters

    $filters      = preg_split ('/\s*(?<!\|)\|(?!\|)\s*/', $expression);
    $expression = array_shift ($filters);
    $v          = null;

    // Call a previously compiled expression or compile one now, cache it and call it.

    /** @var \Closure $compiled */
    $compiled = get (Context::$compiledExpressions, $expression);
    if (!$compiled)
      $compiled = Context::$compiledExpressions[$expression] = $this->compileExpression ($expression);
    // Compatible with PHP < 7
    $c = \Closure::bind ($compiled, $this, $this);
    $v = $c ();

    // Apply filters to expression result

    foreach ($filters as $exp) {

      // Parse each consecutive filter expression, with syntax: filter arg1,...argN

      list ($name, $args) = str_extractSegment ($exp, '/\s+/');
      $args   = $args !== '' ? map (explode (',', $args), [$this, 'evalSimpleExp']) : [];
      $fnArgs = array_from ($v, ...$args);

      if ($name == '*') {
        if ($args) throw new ComponentException ($this,
          "Raw output filter function <kbd>*</kbd> must have no arguments.");
        if (is_null ($v)) continue; // optimization
        $filter = function ($v) { return new \RawText ($v); };
      }
      else $filter = $this->context->getFilter ($name);
      try {
        $v = call_user_func_array ($filter, $fnArgs);
      }
      catch (FilterHandlerNotFoundException $e) {
        throw new ComponentException ($this, "Filter function <kbd>$name</kbd> was not found.");
      }
    }

    // Return the computed value
    return $v;
  }

}
