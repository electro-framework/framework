<?php
namespace Selenia\Routing\Lib;

use Iterator;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Traits\InspectionTrait;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 *
 * > **Note:** instances of this class are immutable.
 */
abstract class BaseRouter implements RouterInterface
{
  use InspectionTrait;

  static public $INSPECTABLE = ['routingEnabled', 'handlers'];
  /**
   * @var bool When false, the iteration keys of the pipeline elements are ignored;
   * when true (the default), they are used as routing patterns.
   */
  public $routingEnabled = true;
  /**
   * **Note:** used by RoutingMiddleware
   * @var array|\Traversable
   */
  protected $handlers;
  /**
   * **Note:** used by RoutingMiddleware
   * @var InjectorInterface
   */
  protected $injector;
  /**
   * @var RouteMatcherInterface
   */
  private $matcher;

  public function __construct (RouteMatcherInterface $matcher, InjectorInterface $injector)
  {
    $this->matcher  = $matcher;
    $this->injector = $injector;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return empty($this->handlers) ? $next () : $this->route ($this->handlers, $request, $response, $next);
  }

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
    $class = get_class ($this);
    /** @var static $new */
    $new = new $class ($this->injector, $this->matcher);
    return $new->set ($handlers);
  }

  /**
   * Performs the actual routing.
   *
   * @param mixed                  $routable
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   */
  function route ($routable, ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (is_null ($routable))
      return $next ();

    if (is_callable ($routable)) {
      if ($routable instanceof FactoryRoutable) {
        $instance = $this->runFactory ($routable);
        return $this->route ($instance, $request, $response, $next);
      }
      else $response = $this->callHandler ($routable, $request, $response, $next);
    }
    else {
      if ($routable instanceof \IteratorAggregate)
        $routable = $routable->getIterator ();

      elseif (is_array ($routable))
        $routable = new \ArrayIterator($routable);

      if ($routable instanceof Iterator)
        $response = $this->iteration_start ($routable, $request, $response, $next);

      elseif (is_string ($routable)) {
        $routable = $this->injector->make ($routable);

        if (is_callable ($routable))
          $response = $this->callHandler ($routable, $request, $response, $next);

        else throw new \RuntimeException (sprintf ("Instances of class <span class=class>%s</span> are not routable.",
          getType ($routable)));
      }
      else throw new \RuntimeException (sprintf ("Invalid routable type <span class=type>%s</span>.",
        getType ($routable)));
    }

    return $response;
  }

  /**
   * Invokes a handler.
   *
   * <p>The router does not call handlers directly; instead, it does it trough this method, so that calls can be
   * intercepted, validated and logged.
   *
   * > This also works as a router extension point.
   *
   * @param callable               $handler
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   */
  protected function callHandler (callable $handler, ServerRequestInterface $request, ResponseInterface $response,
                                  callable $next)
  {
    $response = $handler ($request, $response, $next);

    if (!$response)
      throw new \RuntimeException (sprintf (
        "Request handler <span class=__type>%s</span> did not return a response.",
        Debug::getType ($handler)
      ));

    if (!$response instanceof ResponseInterface)
      throw new \RuntimeException (sprintf (
        "Response from request handler <span class=__type>%s</span> is not a <span class=type>ResponseInterface</span> implementation.",
        Debug::getType ($handler)
      ));

    return $response;
  }

  /**
   * Begins iterating the handler pipeline while each handler calls its `$next` argument, otherwise, it returns the HTTP
   * response.
   * > This also works as a router extension point.
   *
   * @param Iterator               $it
   * @param ServerRequestInterface $currentRequest
   * @param ResponseInterface      $currentResponse
   * @param callable               $nextHandlerAfterIteration
   * @return ResponseInterface
   */
  protected function iteration_start (Iterator $it, ServerRequestInterface $currentRequest,
                                      ResponseInterface $currentResponse, callable $nextHandlerAfterIteration)
  {
    $nextIterationClosure =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null, $first = false)
      use ($it, &$nextIterationClosure, $nextHandlerAfterIteration, &$currentRequest, &$currentResponse) {

        $request  = $currentRequest = ($request ?: $currentRequest);
        $response = $currentResponse = ($response ?: $currentResponse);

        if ($first) $it->rewind ();
        else $it->next ();

        return $it->valid ()
          ? $this->iteration_step ($it->key (), $it->current (), $request, $response, $nextIterationClosure)
          : $this->iteration_stop ($request, $response, $nextHandlerAfterIteration);
      };

    return $nextIterationClosure ($currentRequest, $currentResponse, true);
  }

  /**
   * Invoked when a route iteration step takes place.
   * > This also works as a router extension point.
   *
   * @param string                      $key
   * @param mixed                       $routable
   * @param ServerRequestInterface|null $request
   * @param ResponseInterface|null      $response
   * @param callable                    $nextIteration
   * @return ResponseInterface
   */
  protected function iteration_step ($key, $routable, ServerRequestInterface $request = null,
                                     ResponseInterface $response = null, callable $nextIteration)
  {
    if ($this->routingEnabled && !is_int ($key)) {
      // Route matching:
      if (!$this->matcher->match ($key, $request, $request)) // note: $request may be modified.
        return $nextIteration ($request);

      return $this->iteration_stepMatchRoute ($key, $routable, $request, $response, $nextIteration);
    }
    // Else, a middleware unconditional invocation will be performed.
    return $this->iteration_stepMatchMiddleware ($key, $routable, $request, $response, $nextIteration);
  }

  /**
   * Invoked when a route iteration step matches a middleware.
   * > The main purpose of this method is to provide a router extension point.
   *
   * @param string                 $key
   * @param mixed                  $routable
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $nextIteration
   * @return ResponseInterface
   */
  protected function iteration_stepMatchMiddleware ($key, $routable, ServerRequestInterface $request,
                                                    ResponseInterface $response, callable $nextIteration)
  {
    return $this->route ($routable, $request, $response, $nextIteration);
  }

  /**
   * Invoked when a route iteration step matches a route.
   * > The main purpose of this method is to provide a router extension point.
   *
   * @param string                 $key
   * @param mixed                  $routable
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $nextIteration
   * @return ResponseInterface
   */
  protected function iteration_stepMatchRoute ($key, $routable, ServerRequestInterface $request,
                                               ResponseInterface $response, callable $nextIteration)
  {
    return $this->route ($routable, $request, $response, $nextIteration);
  }

  /**
   * Invoked when route iteration ended without a final response being generated.
   * > The main purpose of this method is to provide a router extension point.
   *
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   */
  protected function iteration_stop (ServerRequestInterface $request, ResponseInterface $response,
                                     callable $next)
  {
    return $next ($request, $response);
  }

  /**
   * Runs a given factory routable.
   * > The main purpose of this method is to provide a router extension point.
   *
   * @param FactoryRoutable $factory
   * @return mixed A routable instance.
   */
  protected function runFactory (FactoryRoutable $factory)
  {
    return $factory ($this->injector);
  }

}

