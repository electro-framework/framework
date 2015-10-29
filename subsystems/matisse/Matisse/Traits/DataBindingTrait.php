<?php
namespace Selenia\Matisse\Traits;

use Selenia\Matisse\DataSource;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\HandlerNotFoundException;

/**
 * Provides an API for handling data binding on a component's properties.
 *
 * It's applicable to the Component class.
 */
trait DataBindingTrait
{
  /**
   * Finds binding expressions and extracts datasource and field info.
   * > Note: the u modifier allows unicode white space to be properly matched.
   */
  static private $PARSE_PARAM_BINDING_EXP = '#
    \{\{\s*
    (?:
      ! ([\w\-]+) \.?
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
  public $defaultDataSource;
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
      throw new DataBindingException($this, "Invalid databinding expression: $bindExp\n" . $e->getMessage ());
    }
  }

  protected function evalBindingExp ($matches, $allowFullSource = false)
  {
    if (empty($matches))
      throw new \InvalidArgumentException();
    list($full, $dataSource, $dataField) = $matches;
    $dataSource = trim ($dataSource);
    $dataField  = trim ($dataField);
    $p          = strpos ($dataField, '{');
    if ($p !== false && $p >= 0) {
      //recursive binding expression
      $exp = preg_replace_callback (self::$PARSE_PARAM_BINDING_EXP, [$this, 'evalBindingExp'], $dataField);
      $z   = strpos ($exp, '.');
      if ($z !== false) {
        $dataSource .= substr ($exp, 0, $z);
        $dataField = substr ($exp, $z + 1);

        return "{!$dataSource.$dataField}";
      }
      else
        return empty($dataSource) ? '{' . "$exp}" : "{!$dataSource" . ($p == 0 ? '' : '.') . "$exp}";
    }
    if (empty($dataSource))
      $src = $this->getDefaultDataSource ();
    else {
      $src = get ($this->context->dataSources, $dataSource);
      if (!isset($src))
        throw new DataBindingException($this, "Data source <b>$dataSource</b> is not defined.");
    }
    if ($dataField == '') {
      if ($allowFullSource)
        return $src;
      throw new DataBindingException($this,
        "The full data source reference <b>$full</b> cannot be used on a composite databinding expression.");
    }
    if (is_null ($src))
      return null;
    if (!method_exists ($src, 'getIterator'))
      throw new DataBindingException($this,
        'Data source ' . (empty($dataSource) ? '<i>default</i>' : "<b>$dataSource</b>") .
        ' is not a valid DataSource object.');
    $it = $src->getIterator ();
    /** @var \Iterator $it */
    if (!$it->valid ())
      return null;
    $pipes     = preg_split ('/\s*\|\s*/', $dataField);
    $dataField = array_shift ($pipes);
    switch ($dataField) {
      case '#key':
        $v = $it->key ();
        break;
      case '#ord':
        $v = $it->key () + 1 + $this->rowOffset;
        break;
      case '#alt':
        $v = $it->key () % 2;
        break;
      default:
        $rec = $it->current ();
        if (is_null ($rec)) {
          $it->rewind ();
          $rec = $it->current ();
        }
        if (is_null ($rec))
          $rec = new \EmptyIterator();
        $v = $dataField == '#self' ? $rec : getField ($rec, $dataField);
    }
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
   * Returns the data source to be used for non qualified databinging expressions.
   * Searches upwards on the component hierarchy.
   *
   * @return DataSource
   */
  protected function getDefaultDataSource ()
  {
    return isset($this->defaultDataSource)
      ? $this->defaultDataSource
      :
      (isset($this->parent) ? $this->parent->getDefaultDataSource () : null);
  }

}
