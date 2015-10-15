<?php
namespace Selenia\Http;

use Auryn\Injector;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\MiddlewareInterface;

class MiddlewareStack
{
  /**
   * @var RequestInterface
   */
  private $currentRequest;
  /**
   * @var ResponseInterface
   */
  private $currentResponse;
  /**
   * @var Injector
   */
  private $injector;
  /**
   * @var array Array of middleware instances or class names.
   *            If a class name is specified, the middleware will be lazily created.
   */
  private $stack = [];

  function __construct (Injector $injector)
  {
    $this->injector = $injector;
  }

  function add ($middlewareClass)
  {
    $this->stack[] = $middlewareClass;

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

  /**
   * @param RequestInterface  $request
   * @param ResponseInterface $response
   * @return ResponseInterface
   */
  function run (RequestInterface $request, ResponseInterface $response)
  {
    $it   = new \ArrayIterator($this->stack);
    $next = null;

    $next = function (RequestInterface $request, ResponseInterface $response) use ($it, $request, $next) {
      if ($it->valid ()) {
        // Make the current state available trough the injector.
        $this->currentRequest  = $request;
        $this->currentResponse = $response;

        /** @var \Selenia\Subsystems\Interfaces\MiddlewareInterface $middleware */
        $m = $it->current ();
        $it->next ();

        // Fetch or instantiate the middleware and run it.
        $middleware  = is_string ($m) ? $this->injector->make ($m) : $m;
        $newResponse = $middleware ($request, $response, $next);

        // Replace the response if necessary.
        if (isset($newResponse)) {
          if (!$newResponse instanceof ResponseInterface)
            throw new \RuntimeException ("Response from middlware " . get_class ($middleware) .
                                         " is not a ResponseInterface implementation.");

          return $newResponse;
        }
      }

      return $response;
    };

    return $next($request, $response);
  }
}
