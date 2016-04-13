<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;
use Selenia\Matisse\Properties\Base\AbstractProperties;

/**
 * Provides a data context for evaluating expressions and methods to manage that context.
 */
interface DataBinderInterface
{
  /**
   * Executes a filter with the given arguments.
   *
   * @param string $name    Filter name.
   * @param array  ...$args Filter arguments. The first argument is always the filter's implicit argument.
   * @return mixed
   * @throws FilterHandlerNotFoundException if the filter is not found.
   */
  function filter ($name, ...$args);

  /**
   * Gets a value with the given name from the view model.
   *
   * @param string $key
   * @return mixed null if not found.
   * @throws DataBindingException
   */
  function get ($key);

  /**
   * Gets the bound component properties.
   *
   * @return $this|null|AbstractProperties
   */
  function getProps ();

  /**
   * Gets the bound view model.
   *
   * @return $this|null|object|array
   */
  function getViewModel ();

  /**
   * Gets a value with the given name from the bound component properties.
   *
   * @param string $key
   * @return mixed null if not found.
   * @throws DataBindingException
   */
  function prop ($key);

  /**
   * Renders a content block for a {#block} reference on an expression.
   *
   * @param string $name The block name.
   * @return string The rendered markup.
   */
  function renderBlock ($name);

  /**
   * Gets a new binder instance with the given view isolation mode.
   *
   * @param bool $isolated
   * @return static
   */
  function withIsolation ($isolated = true);

  /**
   * Gets a new binder instance with the given component properties.
   *
   * @param AbstractProperties|null $props
   * @return static
   */
  function withProps (AbstractProperties $props = null);

  /**
   * Gets a new binder instance with the given component properties.
   *
   * @param object|array|null $viewModel
   * @return static
   */
  function withViewModel ($viewModel);

}
