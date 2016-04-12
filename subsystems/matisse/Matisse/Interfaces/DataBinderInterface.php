<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;

/**
 * An API for the view's data-binding context.
 *
 * It provides a data context for evaluating expressions and methods to manage that context.
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
   * Gets a value with the given name from the scope.
   *
   * @param string $key
   * @return mixed null if not found.
   * @throws DataBindingException
   */
  function get ($key);

  /**
   * Removes a scope from the stack.
   *
   * @return void
   */
  function pop ();

  /**
   * Pushes a scope to the stack.
   *
   * @param array|object $scope
   * @return void
   */
  function push ($scope);

  /**
   * Renders a content block for a {#block} reference on an expression.
   *
   * @param string $name The block name.
   * @return string The rendered markup.
   */
  function renderBlock ($name);

}
