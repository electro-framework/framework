<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;

/**
 * States the requirements that a class must have for its instances to be allowed as databinding expression contexts.
 *
 * <p>A databinding expression context is an object that will be used as a starting point for evaluating expressions.
 * > <p>**Note:** Matisse components implement this interface.
 */
interface ExpressionContextInterface
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
   * Renders a content block.
   *
   * @param string $name The block name.
   * @return string
   */
  function renderBlock ($name);
}
