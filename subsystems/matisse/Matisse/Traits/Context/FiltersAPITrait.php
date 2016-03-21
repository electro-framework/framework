<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components;

/**
 * Manages Matisse rendering filters.
 */
trait FiltersAPITrait
{
  /**
   * A class instance who's methods provide filter implementations.
   *
   * The handler can be an instance of a proxy class, which dynamically resolves the filter invocations trough a
   * `__call` method.
   *
   * > Filter Handlers should throw an exception if a handler method is not found.
   *
   * > <p>An handler implementation is available on the {@see FilterHandler} class.
   *
   * @var object
   */
  private $filterHandler;

  /**
   * @param string $name
   * @return callable A function that implements the filter.
   *                  <p>Note: the function may throw an {@see HandlerNotFoundException} if it can't handle
   *                  the required filter.
   */
  function getFilter ($name)
  {
    if (!isset($this->filterHandler))
      throw new \RuntimeException ("Can't use filters if no filter handler is set.");
    return [$this->filterHandler, $name];
  }

  /**
   * @return object
   */
  function getFilterHandler ()
  {
    return $this->filterHandler;
  }

  /**
   * @param object $filterHandler
   */
  function setFilterHandler ($filterHandler)
  {
    $this->filterHandler = $filterHandler;
  }

}
