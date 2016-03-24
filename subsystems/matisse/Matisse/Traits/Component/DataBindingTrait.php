<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Expression;
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
   * A map of attribute names and corresponding databinding expressions.
   * Equals NULL if no bindings are defined.
   *
   * > <p>It has `public` visibility so that it can be inspected externally.
   *
   * @var Expression[]
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

  /**
   * Registers a data binding.
   *
   * @param string $prop    The name of the bound attribute.
   * @param string $bindExp The binding expression.
   */
  function addBinding ($prop, $bindExp)
  {
    if (!isset($this->bindings))
      $this->bindings = [];
    $this->bindings[$prop] = $bindExp;
  }

  /**
   * Executes a filter with the given arguments.
   *
   * <p>This is used by compiled databinding expressions.
   *
   * @param string $name    Filter name.
   * @param array  ...$args Filter arguments. The first argument is always the filter's implicit argument.
   * @return mixed
   * @throws FilterHandlerNotFoundException if the filter is not found.
   */
  function filter ($name, ...$args)
  {
    $filter = $this->context->getFilter ($name);
    return call_user_func_array ($filter, $args);
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
  function getComputedPropValue ($name)
  {
    if (isset($this->bindings[$name]))
      return $this->evalBinding ($this->bindings[$name]);

    return $this->props->get ($name);
  }

  /**
   * Checks of a property is bound to a databinding expression.
   *
   * @param string $prop A property name.
   * @return bool
   */
  function isBound ($prop)
  {
    return isset($this->bindings) && array_key_exists ($prop, $this->bindings);
  }

  /**
   * Checks if an array offset exists.
   *
   * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
   * @param mixed $offset
   * @return bool
   */
  function offsetExists (/** @noinspection PhpUnusedParameterInspection */
    $offset)
  {
    return false;
  }

  /**
   * Gets a field from the current databinding context.
   *
   * > <p>**Note:** this is meant for internal use by compiled databinding expressions.
   *
   * @param string $field
   * @return mixed
   * @throws DataBindingException
   */
  function offsetGet ($field)
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
      return $parent[$field];

    $data = $this->context->viewModel;
    if (isset($data)) {
      $v = _g ($data, $field, $this);
      if ($v !== $this)
        return $v;
    }

    return null;
  }

  /**
   * Array offset to set
   *
   * @link  http://php.net/manual/en/arrayaccess.offsetset.php
   * @param mixed $offset
   * @param mixed $value
   */
  function offsetSet ($offset, $value)
  {
    //no-op
  }

  /**
   * Array offset to unset
   *
   * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
   * @param mixed $offset
   */
  function offsetUnset ($offset)
  {
    //no-op
  }

  /**
   * Removes the binding from a given property, if one exists.
   *
   * @param string $prop A property name.
   */
  function removeBinding ($prop)
  {
    if (isset($this->bindings)) {
      unset($this->bindings[$prop]);
      if (empty($this->bindings))
        $this->bindings = null;
    }
  }

  /**
   * Renders a content block. This is reserved for use by compiled databinding expressions.
   *
   * @param string $name The block name.
   * @return string
   */
  function renderBlock ($name)
  {
    $block = $this->context->getBlock ($name);
    /** @var Component $this */
    return $this->attachSetAndGetContent ($block);
  }

  /**
   * Evaluates all of the component's bindings.
   *
   * @throws ComponentException
   */
  protected function databind ()
  {
    if (isset($this->bindings))
      foreach ($this->bindings as $attrName => $bindExp) {
        $value = $this->evalBinding ($bindExp);
        if (is_object ($value))
          $this->props->$attrName = $value;
        else $this->props->set ($attrName, $value);
      }
  }

  /**
   * Evaluates the given binding expression on the component's context.
   *
   * <p>This method is an **extension hook** that allows a subclass to modify the evaluation result.
   * > <p>**Ex.** see the {@see Text} component.
   *
   * @param Expression $bindExp
   * @return mixed
   * @throws ComponentException
   * @throws DataBindingException
   */
  protected function evalBinding (Expression $bindExp)
  {
    try {
      /** @var Component $this */
      return $bindExp->evaluate ($this);
    }
    catch (FilterHandlerNotFoundException $e) {
      throw new ComponentException ($this, $e->getMessage ());
    }
  }

  /**
   * Parses a component iterator property. Iterators are used by the `For` component, for instance.
   *
   * @param string $exp
   * @param string $idxVar
   * @param string $itVar
   * @throws ComponentException
   */
  protected function parseIteratorExp ($exp, & $idxVar, & $itVar)
  {
    if (!preg_match ('/^(?:(\w+):)?(\w+)$/', $exp, $m))
      throw new ComponentException($this,
        "Invalid value for attribute <kbd>as</kbd>.<p>Expected syntax: <kbd>'var'</kbd> or <kbd>'index:var'</kbd>");
    list (, $idxVar, $itVar) = $m;
  }

}
