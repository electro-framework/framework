<?php
namespace Selenia\Matisse\Parser;

use PhpCode;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\DataBindingException;

/**
 * Represents a Matisse databinding expression.
 *
 * <p>An instance of this class represents one expression and it provides functionality for:
 * - parsing it;
 * - translating it to PHP source code;
 * - compiling it to native code.
 *
 * ### Databinding expressions features and syntax
 * An expression is composed of two parts:
 * - the main expression;
 * - a sequence of filter sub-expressions, each one prefixed by the pipe (`|`) operator.
 *
 * The main expression is similar to a PHP expression, but with the following differences:
 * - The dot (`.`) operator provides access to a property, array index, or getter function of the left operand.
 *   The right operand must be an unquoted symbol.<br>
 *   Ex: `'obj.prop1.prop2'`
 * - The first element of a dot-delimited sequence is searched for in a stack of nested view models, starting on the
 *   current component.
 * - Consecutive segments are evaluated in a way that is resilient to errors; dereferencing null or accessing
 *   non-existing indexes or properties is valid and evaluates to an empty string.
 * - The plus (`+`) operator concatenates strings. This means you cannot use it to perform numerical calculations.
 *   If you need them, pre-compute the values on the controller (that's the right place to do it).
 * - `'#block'` evaluates to the rendering of the specified block; it cannot be followed by a dot.
 * - '&#64;prop' evaluates to the specified property of the current composite component container.
 *
 * ### Constants
 * Expressions can contain one or more constants between operators.
 * <p>Valid constants are:
 *   - 123 or 123.4
 *   - `"string"` or `'string'`
 *   - `true, false, null`
 *   - any PHP constant defined via `define()` or `const`
 *   - `namespace\class::constant` (`self::` or `static::` are not valid)
 *
 * ### Filters
 * Syntax: `filterName arg1,...argN`
 *
 * <p>Ex:
 * ```
 *   "obj.prop | then 'true','false'"
 *   "onj.prop | format '%.3f' | json"
 * ```
 */
class Expression
{
  /**
   * Splits the filters part of an expression into a sequential list of filter expressions.
   */
  const PARSE_FILTER = '/\s*(?<!\|)\|(?!\|)\s*/';
  /**
   * Extracts a simple expression segment from a dot-delimited list of segments.
   * <p>Ex: `'a.b.c'`
   */
  const PARSE_SIMPLE_EXPR = '/
    ^\s*                      # ignore white space at the beginning
    (                         # capture either
      [@#]?[:\w]+             # a constant name, a property name (ex: "prop", "@prop"), a block name (ex: "#prop") or a class constant (ex: MyClass::myConstant)
      |                       # or
      \'(?:(?<!\\\\)[^\'])*\' # a quoted string constant (supports escaped quotes inside the string)
      |                       # or
      "(?:(?<!\\\\)[^"])*"    # a double quoted string constant (supports escaped double quotes inside the string)
      |                       # or
      !                       # the unary `not` operator, which will became part of the captured segment
    )
    \s*                       # ignore white space
    (                         # capture the next operator, if one is present, including the the first filter\'s pipe
      \|(?!\|)                # capture the pipe operator (but not the || operator)
      |                                 # or
      [,\|\.\/\-\(\)\[\]\{\}%&=?*+<>]+  # one of the other allowed operators
    )?
    /xu';
  /**
   * A map of databinding expressions to compiled functions.
   *
   * @var \Closure[] [string => Closure]
   */
  public static $cache = [];
  /**
   * Finds binding expressions and extracts information from them.
   * > Note: the u modifier allows unicode white space to be properly matched.
   */
  static private $PARSE_BINDING_EXP = '/
    \{              # opens with {
    \s*             # ignore white space
    (               # begin capture
      (?:           # begin loop
        (?! \s*\})  # if not white space followed by a }
        .           # consume character
      )*            # repeat
    )               # end capture
    \s*             # ignore white space
    \}              # closes with }
  /xu';
  /**
   * @var \Closure|null A function that receives a context argument and returns the evaluated value.
   */
  public $compiled = null;
  /**
   * @var string|null The original expression translated to PHP code.
   */
  public $translated = null;
  /**
   * The original, unparsed, expression.
   *
   * <p>To read this, cast the instance to `string` or call {@see __toString()}.
   *
   * @var string
   */
  private $expression;

  function __construct ($expression)
  {
    if (Expression::isCompositeBinding ($expression))
      throw new DataBindingException("Multiple binding expressions on a string are not supported: <kbd>$expression</kbd>
<p>Convert it to a single expression using the <kbd>+</kbd> string concatenation operator.");

    if (!preg_match (self::$PARSE_BINDING_EXP, $expression, $matches))
      throw new DataBindingException("Invalid databinding expression: $expression");

    list ($full, $this->expression) = $matches;
  }

  public static function isBindingExpression ($exp)
  {
    return is_string ($exp) ? strpos ($exp, '{') !== false : false;
  }

  public static function isCompositeBinding ($exp)
  {
    return $exp[0] != '{' || substr ($exp, -1) != '}' || strpos ($exp, '{', 1) > 0;
  }

  /**
   * Pre-compiles the given simple binding expression.
   *
   * <p>Simple expressions do not have operators or filters. They are comprised of constants of property access chains
   * only.
   *
   * <p>**Ex:** `'a.b.c'`, `'123'`, `'"text"'`, `'false'`, `'Class::constant'`, '&#64;prop', `'#block'`.
   *
   * > <p>**Note:** simple expressions are used on the main part of a databinding expression and as expression filter
   * arguments.
   *
   * @param string[] $segments Expression segments split by dot.
   * @return string
   * @throws DataBindingException
   */
  static function translateSimpleExpSegs (array $segments)
  {
    if (count ($segments) == 1) {
      $seg = $segments[0];

      if ($seg[0] == '#')
        return '$this->renderBlock("' . substr ($seg, 1) . '")';

      PhpCode::evalConstant ($seg, $ok);
      if ($ok) return $seg;
    }

    if ($segments[0][0] == '@')
      array_splice ($segments, 0, 1, ['props', substr ($segments[0], 1)]);

    $exp = $not = '';
    foreach ($segments as $i => $seg) {
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
      throw new DataBindingException(sprintf ("Invalid expression <kbd>%s</kbd>.", implode ('.', $segments)));
    return $exp;
  }

  /**
   * Compiles a databinding expression.
   *
   * @param string $expression
   * @return \Closure
   */
  static private function compile ($expression)
  {
    inspect ("Compile $expression");
    list ($main, $op) = self::translateSimpleExpression ($expression);

    $exp = $main;
    if ($op == '|') {
      $filters = preg_split (self::PARSE_FILTER, $expression);
      if ($filters)
        foreach ($filters as $filter)
          $exp = self::translateFilter ($filter, $exp);
    }

    inspect ($exp);
    return PhpCode::compile ($exp);
  }

  /**
   * Precompiles a single filter sub-expression, composed of a filter name and a list of optional comma-delimited
   * arguments.
   *
   * @param string $filter Rhe filter expression.
   * @param string $input  The implicit input to the filter (the sub-expression before the pipe)
   * @return string
   * @throws DataBindingException
   */
  static private function translateFilter ($filter, $input)
  {
    // Filter expressions syntax: filter arg1,...argN

    list ($name, $argsStr) = str_extractSegment ($filter, '/\s+/');
    $args = [];

    while ($argsStr !== '') {
      list ($subExp, $op) = self::translateSimpleExpression ($argsStr);
      if ($op && $op != ',')
        throw new DataBindingException ("Filter arguments must be simple expressions; operators are not allowed.
<p>Expression: <kbd>$filter</kbd>, invalid operator: <kbd>$op</kbd>");
      $args[] = $subExp;
    }

    if ($name == '*') {
      if ($args) throw new DataBindingException ("Raw output filter function <kbd>*</kbd> must have no arguments.");
      return "(new RawText($input))";
    }
    return sprintf ('$this->filter(\'%s\',%s%s%s)', $name, $input, $args ? ',' : '', implode (',', $args));
  }

  /**
   * @param string $expression [reference] The parsed sub-expression will be removed from the input expression.
   * @return string[] The extracted sub-expression and the next operator (if any).
   * @throws DataBindingException
   */
  static private function translateSimpleExpression (& $expression)
  {
    $op         = $subExp = '';
    $segs       = [];
    $expression = trim ($expression);
    while ($expression !== '' && preg_match (self::PARSE_SIMPLE_EXPR, $expression, $m)) {
      $m[] = '';
      list ($all, $seg, $op) = $m;
      $expression = trim (substr ($expression, strlen ($all)));
      $segs[]     = $seg;
      if ($op == '.') continue;
      if ($op == '|' || $op == ',') break;
      if ($op == '+') $op = '.';
      $subExp .= self::translateSimpleExpSegs ($segs) . $op;
      $segs = [];
    }
    if ($segs)
      $subExp .= self::translateSimpleExpSegs ($segs);
    return [$subExp, $op];
  }

  /**
   * Returns the original, unparsed, expression.
   *
   * @return string
   */
  function __toString ()
  {
    return $this->expression;
  }

  /**
   * Computes the expression's value applied on the context of the given component.
   *
   * <p>This automatically compiles and caches the expression, if it's not already so.
   *
   * @param Component $component
   * @return mixed
   */
  function evaluate (Component $component)
  {
    if (!($fn = $this->compiled)) {
      $fn = get (self::$cache, $this->expression);
      if ($fn)
        $this->compiled = $fn;
      else {
        $fn = $this->compiled = self::compile ($this->expression);
        // Cache the compiled expression.
        Expression::$cache[$this->expression] = $fn;
      }
    }
    $fn = \Closure::bind ($fn, $component, $component);
    return $fn ();
  }

}
