<?php
namespace Selenia\Matisse\Traits;

use PhpCode;
use Selenia\Matisse\DataSource;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\HandlerNotFoundException;
use Selenia\Matisse\MatisseEngine;

/**
 * Provides an API for handling data binding on a component's properties.
 *
 * It's applicable to the Component class.
 */
trait DataBindingTrait
{
  static private $MODEL_DATASOURCE_NAME = 'model';
  /**
   * Finds binding expressions and extracts datasource and field info.
   * > Note: the u modifier allows unicode white space to be properly matched.
   */
  static private $PARSE_PARAM_BINDING_EXP = '#
    \{\{\s*
    (?:
      % ([\w\-]+) \.?
    )?
    (
      (?:
        [^{}]* | \{ [^{}]* \}
      )*
    )?
    \s*\}\}
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
  public $modelDataSource;
  /**
   * Set by Repeater components for supporting pagination.
   * @var int
   */
  public $rowOffset = 0;

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
          $bindExp = $this->evalBindingExp ($matches, true);
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

  protected function evalBindingExp ($matches, $allowFullSource = false)
  {
    if (empty($matches))
      throw new \InvalidArgumentException;
    list($full, $injectable, $expression) = $matches;
    $injectable = trim ($injectable);
    $expression = trim ($expression);
    $p          = strpos ($expression, '{');

    // Recursive binding expression.

    if ($p !== false && $p >= 0) {
      $exp = preg_replace_callback (self::$PARSE_PARAM_BINDING_EXP, [$this, 'evalBindingExp'], $expression);
      $z   = strpos ($exp, '.');
      if ($z !== false) {
        $injectable .= substr ($exp, 0, $z);
        $expression = substr ($exp, $z + 1);

        return "{{%$injectable.$expression}}";
      }
      else
        return empty($injectable) ? '{{' . "$exp}}" : "{{%$injectable" . ($p == 0 ? '' : '.') . "$exp}}";
    }

    // No injectable was specified:
    if (empty($injectable))
      $src = $this->context->viewModel;

    // Use injected value as view-model.
    else {
      $fn = $this->context->injectorFn;
      $src = $fn ($injectable);
    }

    if ($expression == '') {
      if ($allowFullSource)
        return $src;
      throw new DataBindingException($this,
        "The full data source reference <b>$full</b> cannot be used on a composite databinding expression.");
    }

    // Ignore macro arguments.
    if ($expression[0] == '@')
      return null;

    if (is_null ($src))
      $src = [];

    // Parse pipes

    $pipes      = preg_split ('/\s*\|\s*/', $expression);
    $expression = array_shift ($pipes);

    // Virtual fields (#xxx)

    if ($expression[0] == '#') {
      if (is_array ($src)) {
        $v = current ($src);
        $k = key ($src);
      }
      else if ($src instanceof \Traversable) {
        $it = iterator ($src);
        $v  = $it->current ();
        $k  = $it->key ();
      }
      else throw new DataBindingException($this,
        "Can't use virtual field $expression on a non-iterable data source.");
      switch ($expression) {
        case '#key':
          $v = $k;
          break;
        case '#ord':
          $v = $k + 1 + $this->rowOffset;
          break;
        case '#alt':
          $v = $k % 2;
          break;
        case '#self': // $v = $v
          break;
        default:
          throw new DataBindingException($this,
            "Unsupported virtual field $expression.");
      }
    }

    // Evaluate expression

    else {
      if ($src instanceof \Traversable) {
        $it  = iterator ($src);
        $src = $it->current ();
      }

      // Context-relative expression:
      if ($expression[0] == '.') {
        $src        = $this->getContextualModel ();
        $expression = substr ($expression, 1);
      }

      // Call a previously compiled expression or compile one now, cache it and call it.

      /** @var \Closure $compiled */
      $compiled = get (MatisseEngine::$expressions, $expression);
      if (!$compiled) {
        $args = explode ('.', $expression);
        $exp  = '$src';
        foreach ($args as $arg)
          $exp = "getField($exp,'$arg')";
        if (!PhpCode::validateExpression ($exp))
          throw new DataBindingException($this, "Invalid expression <kbd>$expression</kbd>.");
        $compiled = MatisseEngine::$expressions[$expression] = PhpCode::compile ($exp, '$src');
      }
      else _log ("CACHE HIT $injectable.$expression");

      $v = $compiled ($src);
    }

    // Apply pipes to expression result

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
    if (isset($this->modelDataSource))
      return $this->modelDataSource;
    /** @var static $parent */
    $parent = $this->parent;
    return isset($parent) ? $parent->getContextualModel () : null;
  }

}
