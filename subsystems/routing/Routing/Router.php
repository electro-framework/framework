<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 *
 * > **Note:** instances of this class are immutable.
 */
class Router implements RouterInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var callable
   */
  private $next;
  /**
   * @var ServerRequestInterface
   */
  private $request;
  /**
   * @var ResponseInterface
   */
  private $response;

  public function __construct (InjectorInterface $injector, ServerRequestInterface $request,
                               ResponseInterface $response, callable $next)
  {
    $this->injector = $injector;
    $this->request  = $request;
    $this->response = $response;
    $this->next     = $next;
  }

  function route ($routable)
  {
    if (is_callable ($routable)) {
      if ($routable instanceof FactoryRoutable)
        $response = $this->route ($routable ($this->injector));

      else $response = $routable ($this->request, $this->response, $this->next /* ??? */);
    }
    else {
      if ($routable instanceof \IteratorAggregate)
        $routable = $routable->getIterator ();

      else if (is_array ($routable))
        $routable = new \ArrayIterator($routable);

      if ($routable instanceof \Iterator)
        $response = $this->iterateHandlers ($routable);

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
    return $response ?: $this->response;
  }

  function with (ServerRequestInterface $request = null, ResponseInterface $response = null, callable $next = null)
  {
    $class = get_class ($this);
    return new $class($this->injector, $request ?: $this->request, $response ?: $this->response, $next ?: $this->next);
  }

  private function iterateHandlers (\Iterator $it)
  {
    $first           = true;
    $callNextHandler =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null) use (
        $it, &$callNextHandler, &$first
      ) {
        if ($first) $first = false;
        else $it->next ();
        if ($it->valid ()) {
          // Save the current state and also make it available outside the stack.

          $request  = $this->request = $request ?: $this->request;
          $response = $this->response = $response ?: $this->response;

          $handler = $it->current ();
          $pattern = $it->key ();

          if (!is_int ($pattern)) {
            if ($this->match ($pattern, $request->getRequestTarget (), $newPath))
              $newResponse = $this
                ->with ($request->withRequestTarget ($newPath), $response, $callNextHandler)
                ->route ($handler);
          }
          else $newResponse = $this->route ($handler);

          // Replace the response if necessary.
          if (isset($newResponse)) {
            if ($newResponse instanceof ResponseInterface)
              return $this->response = $newResponse;

            throw new \RuntimeException (sprintf (
              "Response from request handler <kbd class=type>%s</kbd> is not a ResponseInterface implementation.",
              typeOf ($handler)));
          }
          return $this->response;
        }
        return ($next = $this->next) ? $next ($request, $response) : $response;
      };

    $it->rewind ();
    return $callNextHandler ();
  }

  private function match ($pattern, $getRequestTarget, &$newPath)
  {

  }

}

