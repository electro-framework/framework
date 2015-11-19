<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\RouteMatcherInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 *
 * > **Note:** instances of this class are immutable.
 */
class Router implements RouterInterface
{
  /**
   * @var bool When false, the iteration keys of the pipeline elements are ignored; when true, they are used as routing
   *           patterns.
   */
  public $routingEnabled = true;
  /**
   * @var array|\Traversable
   */
  private $handlers;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var RouteMatcherInterface
   */
  private $matcher;
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

  public function __construct (InjectorInterface $injector, RouteMatcherInterface $matcher)
  {
    $this->injector = $injector;
    $this->matcher  = $matcher;
  }

  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next A function with arguments
   *                                     <kbd>(ServerRequestInterface $request = null,
   *                                     ResponseInterface $response = null)</bkd>
   * @return ResponseInterface
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->request  = $request;
    $this->response = $response;
    $this->next     = $next;
    if (empty($this->handlers))
      return $next ();
    return $this->route ($this->handlers);
  }

  /**
   * Adds a request handler to the pipeline.
   * @param string|callable|RequestHandlerInterface $handler The request handler to be added to the pipeline.
   * @param string|int|null                         $key     An ordinal index or an arbitrary identifier to associate
   *                                                         with the given handler.
   *                                                         <p>If not specified, an auto-incrementing integer index
   *                                                         will be assigned.
   *                                                         <p>If an integer is specified, it may cause the handler to
   *                                                         overwrite an existing handler at the same ordinal position
   *                                                         on the pipeline.
   *                                                         <p>String keys allow you to insert new handlers after a
   *                                                         specific one.
   *                                                         <p>Some RequestHandlerPipelineInterface implementations
   *                                                         may use the key for other purposes (ex. route matching
   *                                                         patterns).
   * @param string|int|null                         $after   Insert after an existing handler that lies at the given
   *                                                         index, or that has the given key. When null, it is
   *                                                         appended.
   * @return $this
   */
  function add ($handler, $key = null, $after = null)
  {
    if (empty($this->handlers))
      $this->handlers = [];
    else if (!is_array ($this->handlers))
      $this->handlers = iterator_to_array ($this->handlers);
    $this->handlers = array_insertAfter ($this->handlers, $after, $handler, $key);
    return $this;
  }

  function set ($handlers)
  {
    if (!is_iterable ($handlers))
      $handlers = [$handlers];
    $this->handlers = $handlers;
    return $this;
  }

  function with ($handlers)
  {
    if (!is_iterable ($handlers))
      $handlers = [$handlers];
    if (empty($this->handlers)) {
      $this->handlers = $handlers;
      return $this;
    }
    $class = get_class ($this);
    /** @var static $new */
    $new = new $class ($this->injector, $this->matcher);
    return $new->with ($handlers);
  }

  /**
   * Performs the actual routing.
   *
   * @param mixed $routable
   * @return ResponseInterface
   */
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

          if (!$this->routingEnabled && !is_int ($pattern)) {
            if ($this->matcher->match ($pattern, $prevPath = $request->getRequestTarget (), $newPath))
              $newResponse = $this
                ->with ($handler)
                ->__invoke
                ($prevPath != $newPath ? $request->withRequestTarget ($newPath) : $request,
                  $response, $callNextHandler);
            else return ($next = $this->next) ? $next () : $response;
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

}

