<?php
namespace Selenia\Routing;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
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
class Router implements RouterInterface
{
  use InspectionTrait;

  static public $INSPECTABLE = ['routingEnabled', 'handlers'];
  /**
   * @var bool When false, the iteration keys of the pipeline elements are ignored;
   * when true (the default), they are used as routing patterns.
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
   * @var ConsoleLogger
   */
  private $logger;
  /**
   * @var RouteMatcherInterface
   */
  private $matcher;
  /**
   * The current request; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var ServerRequestInterface
   */
  private $request;
  /**
   * The current response; updated as the router calls request handlers.
   * @var ResponseInterface
   */
  private $response;

  public function __construct (InjectorInterface $injector, RouteMatcherInterface $matcher)
  {
    $this->injector = $injector;
    $this->matcher  = $matcher;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->request  = $request;
    $this->response = $response;
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
      if ($routable instanceof FactoryRoutable)
        $response = $this->route ($routable ($this->injector), $request, $response, $next);

      else $response = $this->callHandler ($routable, $request, $response, $next);
    }
    else {
      if ($routable instanceof \IteratorAggregate)
        $routable = $routable->getIterator ();

      else if (is_array ($routable))
        $routable = new \ArrayIterator($routable);

      if ($routable instanceof \Iterator)
        $response = $this->iterateHandlers ($routable, $request, $response, $next);

      elseif (is_string ($routable)) {
        $routable = $this->injector->make ($routable);

        if (is_callable ($routable))
          $response = $this->callHandler ($routable, $request, $response, $next);

        else throw new \RuntimeException (sprintf ("Instances of class <kbd class=class>%s</kbd> are not routable.",
          getType ($routable)));
      }
      else throw new \RuntimeException (sprintf ("Invalid routable type <kbd class=type>%s</kbd>.",
        getType ($routable)));
    }
    return $response;
  }

  /**
   * Invokes a handler.
   *
   * <p>The router does not call the handlers directly; instead, it does it trough this method, so that calls can be
   * intercepted, validated and logged.
   *
   * @param callable               $handler
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   */
  private function callHandler ($handler, $request, $response, $next)
  {
    static $c = 1;
    $log = DebugConsole::hasLogger ('routingLog');
    if ($log) {
      $this->logger = DebugConsole::logger ('routingLog');
      $this->logger
                  ->write (sprintf ("<#i|__rowHeader><span>%d</span><#type>%s</#type></#i>", $c++, typeOf ($handler)));
//      $this->logResponse ($response);
    }

    $newResponse = $handler ($request, $response, $next);
    $log         = DebugConsole::hasLogger ('routingLog'); // it may have changed.
    if ($log)
      $this->logger = DebugConsole::logger ('routingLog');
    if ($newResponse) {
      if ($newResponse !== $response) {
        if ($log) {
          $this->logger->write ('<#header>OLD Response</#header>');
          $this->logResponse ($response);
          $this->logger->write ('<#header>NEW Response</#header>');
          $this->logResponse ($newResponse);
        }
        $this->response = $newResponse;
//        if ($log)
//          $this->logResponse ($newResponse);
      }
      // Replace the response if necessary.
      if (!$newResponse instanceof ResponseInterface)
        throw new \RuntimeException (sprintf (
          "Response from request handler <kbd class=type>%s</kbd> is not a <kbd class=type>ResponseInterface</kbd> implementation.",
          typeOf ($handler)));
    }
    else $newResponse = $this->response;
    return $newResponse;
  }

  /**
   * Iterates the handler pipeline while each handler calls its `$next` argument, otherwise, it returns the HTTP
   * response.
   * @param \Iterator              $it
   * @param ServerRequestInterface $requestBeforeIter
   * @param ResponseInterface      $responseBeforeIter
   * @param callable               $nextHandlerBeforeIter
   * @return ResponseInterface
   */
  private function iterateHandlers (\Iterator $it, ServerRequestInterface $requestBeforeIter,
                                    ResponseInterface $responseBeforeIter, callable $nextHandlerBeforeIter)
  {
    $first           = true;
    $callNextHandler =
      function (ServerRequestInterface $request = null, ResponseInterface $response = null) use (
        $it, &$callNextHandler, &$first, $nextHandlerBeforeIter
      ) {
        $request  = $this->request = ($request ?: $this->request);
        $response = $this->response = ($response ?: $this->response);

        if ($first) $first = false;
        else $it->next ();
        if ($it->valid ()) {

          $routable = $it->current ();
          $pattern  = $it->key ();

          if (!$this->routingEnabled && !is_int ($pattern)) {

            // Route matching:

            if (!$this->matcher->match ($pattern, $request, $request))
              return $callNextHandler ();
          }
          return $this->route ($routable, $request, $response, $callNextHandler);
        }
        // Iteration ended.
        return $nextHandlerBeforeIter ($request, $response);
      };

    $it->rewind ();
    return $callNextHandler ($requestBeforeIter, $responseBeforeIter);
  }

  /**
   * @param ResponseInterface $r
   */
  private function logResponse ($r)
  {
    static $uid = 1;
    if (!isset($r->uid))
      $r->uid = $uid++;
//    WebConsole::routingLog ()->write("YES!!!");return;
    $h   = map ($r->getHeaders (), function ($v) { return implode ('<br>', $v); });
    $r->getBody()->rewind();
    $c = preg_replace('#^[\s\S]*<body.*?>([\s\S]*)</body>[\s\S]*$#','$1',$r->getBody()->getContents());
    $out = [
      '#id'     => $r->uid, //DebugConsole::objectId ($r),
      'Status'  => $r->getStatusCode () . ' ' . $r->getReasonPhrase (),
      'Headers' => $h,
      'Size'    => $r->getBody ()->getSize (),
      'Body content'=> substr($c,0,1000).'...'
    ];
    $this->logger->write ('<#indent>')->simpleTable ($out, 'Resulting response')->write ('</#indent>');
  }

}

