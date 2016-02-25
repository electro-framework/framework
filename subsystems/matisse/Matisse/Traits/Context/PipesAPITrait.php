<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components;

/**
 * Manages Matisse rendering pipes.
 */
trait PipesAPITrait
{
  /**
   * A class instance who's methods provide pipe implementations.
   *
   * The handler can be an instance of a proxy class, which dynamically resolves the pipe invocations trough a
   * `__call` method.
   *
   * > Pipe Handlers should throw an exception if a handler method is not found.
   *
   * > <p>An handler implementation is available on the {@see PipeHandler} class.
   *
   * @var object
   */
  private $pipeHandler;

  /**
   * @param string $name
   * @return callable A function that implements the pipe.
   *                  <p>Note: the function may throw an {@see HandlerNotFoundException} if it can't handle
   *                  the required pipe.
   */
  function getPipe ($name)
  {
    if (!isset($this->pipeHandler))
      throw new \RuntimeException ("Can't use pipes if no pipe handler is set.");
    return [$this->pipeHandler, $name];
  }

  /**
   * @return object
   */
  function getPipeHandler ()
  {
    return $this->pipeHandler;
  }

  /**
   * @param object $pipeHandler
   */
  function setPipeHandler ($pipeHandler)
  {
    $this->pipeHandler = $pipeHandler;
  }

}
