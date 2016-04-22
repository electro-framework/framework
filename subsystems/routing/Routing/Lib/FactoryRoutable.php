<?php
namespace Selenia\Routing\Lib;

use Selenia\Interfaces\DI\InjectorInterface;

/**
 * Wraps a routable factory function.
 *
 * When the router is about to invoke a routable, it checks if it is an instance of this class and, if it is, instead
 * of calling it with the standard request handler arguments `($request, $response, $next)`, it invokes it with a single
 * injector argument and then proceeds to handle the result of the call.
 *
 * The factory function itself can have any injectable parameters you wish. It should return a 'routable'.
 */
class FactoryRoutable
{
  /** @var callable */
  private $fn;

  public function __construct (callable $factoryFn)
  {
    $this->fn = $factoryFn;
  }

  /**
   * @param InjectorInterface $injector
   * @return mixed A routable instance.
   */
  function __invoke (InjectorInterface $injector)
  {
    return $injector->execute ($this->fn);
  }

}
