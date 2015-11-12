<?php
namespace Selenia\HttpMiddleware\Services;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\MiddlewareStackInterface;

class MiddlewareStack implements MiddlewareStackInterface
{
  /**
   * @var ServerRequestInterface
   */
  private $currentRequest;
  /**
   * @var ResponseInterface
   */
  private $currentResponse;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var array Array of middleware instances or class names.
   *            If a class name is specified, the middleware will be lazily created.
   */
  private $stack = [];

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
  {
    $it   = new \ArrayIterator($this->stack);

    $next =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null) use ($it, $request, &$next
      ) {
        if ($it->valid ()) {
          // Save the current state and also make it available outside the stack.

          $request  = $this->currentRequest = $request ?: $this->currentRequest;
          $response = $this->currentResponse = $response ?: $this->currentResponse;

          /** @var \Selenia\Interfaces\MiddlewareInterface $middleware */
          $m = $it->current ();
          $it->next ();

          // Fetch or instantiate the middleware and run it.
          $middleware  = is_string ($m) && !is_callable ($m) ? $this->injector->make ($m) : $m;
          $newResponse = $middleware ($request, $response, $next);

          // Replace the response if necessary.
          if (isset($newResponse)) {
            if ($newResponse instanceof ResponseInterface)
              return $this->currentResponse = $newResponse;
            throw new \RuntimeException ("Response from middleware " . get_class ($middleware) .
                                         " is not a ResponseInterface implementation.");
          }
          return $this->currentResponse;
        }
        return $next ? $next ($request, $response) : $response;
      };

    return $next ($request, $response);
  }

  /**
   * @param string|callable|MiddlewareInterface $middleware
   * @return $this
   */
  function add ($middleware)
  {
    $this->stack[] = $middleware;
    return $this;
  }

  /**
   * @param boolean                             $condition
   * @param string|callable|MiddlewareInterface $middleware
   * @return $this
   */
  function addIf ($condition, $middleware)
  {
    if ($condition)
      $this->stack[] = $middleware;
    return $this;
  }

  function getCurrentRequest ()
  {
    return $this->currentRequest;
  }

  function getCurrentResponse ()
  {
    return $this->currentResponse;
  }

}
