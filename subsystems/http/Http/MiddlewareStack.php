<?php
namespace Selenia\Http;

use PhpKit\WebConsole\WebConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\InjectorInterface;

class MiddlewareStack
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
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @return ResponseInterface
   */
  function run (ServerRequestInterface $request, ResponseInterface $response)
  {
    $it   = new \ArrayIterator($this->stack);
    $next = null;

    $next =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null) use ($it, $request, &$next
      ) {
        if ($it->valid ()) {
          // Save the current state and also Make it available trough the injector.
          $request  = $this->currentRequest = $request ?: $this->currentRequest;
          $response = $this->currentResponse = $response ?: $this->currentResponse;

          /** @var \Selenia\Interfaces\MiddlewareInterface $middleware */
          $m = $it->current ();
          $it->next ();

          if (WebConsole::$initialized)
            _log ($m);

          // Fetch or instantiate the middleware and run it.
          $middleware  = is_string ($m) ? $this->injector->make ($m) : $m;
          $newResponse = $middleware ($request, $response, $next);

          // Replace the response if necessary.
          if (isset($newResponse)) {
            if ($newResponse instanceof ResponseInterface)
              return $newResponse;
            throw new \RuntimeException ("Response from middlware " . get_class ($middleware) .
                                         " is not a ResponseInterface implementation.");
          }
        }
        return $response;
      };

    return $next($request, $response);
  }
}
