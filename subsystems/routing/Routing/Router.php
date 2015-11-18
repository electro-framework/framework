<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RouteInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 */
class Router implements RouterInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  private $stack = [];

  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  function next ()
  {
    return $this->stack ? $this->stack[0][2] : null;
  }

  function request ()
  {
    return $this->stack ? $this->stack[0][0] : null;
  }

  function response ()
  {
    return $this->stack ? $this->stack[0][1] : null;
  }

  function route ($routable)
  {
    if (is_callable ($routable)) {
      if ($routable instanceof FactoryRoutable)
        $response = $this->route ($routable ($this->injector));

      else $response = $routable ($this->request (), $this->response (), $this/* ??? */);
    }
    else {
      if ($routable instanceof \IteratorAggregate)
        $routable = $routable->getIterator ();

      else if (is_array ($routable))
        $routable = new \ArrayIterator($routable);

      if ($routable instanceof \Iterator)
        $response = $this->iterateHandlers ($routable, $this->request (), $this->response (), $this->next ()/* ??? */);

      elseif (is_string ($routable)) {
        $routable = $this->injector->make ($routable);
        if (is_callable ($routable))
          $response = $routable ();
        else throw new \RuntimeException (sprintf ("Instances of class <kbd class=class>%s</kbd> are not routable.",
          getType ($routable)));
      }

      else throw new \RuntimeException (sprintf ("Invalid routable type <kbd class=type>%s</kbd>",
        getType ($routable)));
    }
    $this->backtrack ();
    return $response ?: $this->response ();
  }

  function with (ServerRequestInterface $request = null, ResponseInterface $response = null, callable $next = null)
  {
    $this->stack[] = [$request ?: $this->request (), $response ?: $this->response (), $next ?: $this->next ()];
    return $this;
  }

  private function backtrack ()
  {
    if (!array_pop ($this->stack))
      throw new \RuntimeException ("The router can't backtrack to a previous context, because there is none.");
  }

  private function iterateHandlers (\Iterator $it, ServerRequestInterface $request, ResponseInterface $response,
                                    callable $next = null)
  {
    $next =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null) use ($it, $request, &$next
      ) {
        if ($it->valid ()) {
          // Save the current state and also make it available outside the stack.

          $request  = $this->currentRequest = $request ?: $this->currentRequest;
          $response = $this->currentResponse = $response ?: $this->currentResponse;

          /** @var \Selenia\Interfaces\Http\RequestHandlerInterface $middleware */
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

}

