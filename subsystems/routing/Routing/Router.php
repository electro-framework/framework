<?php
namespace Selenia\Routing;

use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Routing\Services\RoutingLogger;
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
   * The current request; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var ServerRequestInterface
   */
  private $currentRequest;
  /**
   * The current request body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var int
   */
  private $currentRequestSize = 0;
  /**
   * The current response; updated as the router calls request handlers.
   * @var ResponseInterface
   */
  private $currentResponse;
  /**
   * The current response body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var int
   */
  private $currentResponseSize = 0;
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
   * @var RoutingLogger
   */
  private $routingLogger;

  public function __construct (InjectorInterface $injector, RouteMatcherInterface $matcher,
                               RoutingLogger $routingLogger)
  {
    $this->injector      = $injector;
    $this->matcher       = $matcher;
    $this->routingLogger = $routingLogger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->currentRequestSize  = $request->getBody ()->getSize ();
    $this->currentResponseSize = $response->getBody ()->getSize ();
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
        $this->routingLogger->write ("<#i|__rowHeader>Factory routable invoked</#i>");
        $response = $this->route ($routable ($this->injector), $request, $response, $next);
      }
      else $response = $this->callHandler ($routable, $request, $response, $next);
    }
    else {
      if ($routable instanceof \IteratorAggregate)
        $routable = $routable->getIterator ();

      elseif (is_array ($routable))
        $routable = new \ArrayIterator($routable);

      if ($routable instanceof \Iterator)
        $response = $this->iterateHandlers ($routable, $request, $response, $next);

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
    //-----------------------------------------------------------------------------------------------
    // DEBUG
    //-----------------------------------------------------------------------------------------------
    $this->routingLogger
      ->write ("<#i|__rowHeader>Call ")->typeName ($handler)->write ("</#i>");

    if ($request && $request != $this->currentRequest) {
      $this->logRequest ($request, sprintf ('with a new %s object:', Debug::getType ($request)));
      $this->currentRequest     = $request;
      $this->currentRequestSize = $request->getBody ()->getSize ();
    }

    if ($response && $response != $this->currentResponse) {
      $this->logRequest ($request, sprintf ('with a new %s object:', Debug::getType ($response)));
      $this->currentResponse     = $response;
      $this->currentResponseSize = $response->getBody ()->getSize ();
    }

    //-----------------------------------------------------------------------------------------------

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

    //-----------------------------------------------------------------------------------------------
    // DEBUG
    //-----------------------------------------------------------------------------------------------
    $this->routingLogger->write ("<#i|__rowHeader>Return from ")->typeName ($handler)->write ('</#i>');

    if ($response !== $this->currentResponse) {
      $this->logResponse ($response, sprintf ('with a new %s object:', Debug::getType ($response)));
      $this->currentResponse     = $response;
      $this->currentResponseSize = $response->getBody ()->getSize ();
    }
    else {
      $newSize = $response->getBody ()->getSize ();
      if ($newSize != $this->currentResponseSize) {
        $this->logResponse ($response, sprintf ('with a modified %s body:', Debug::getType ($response)));
        $this->currentResponseSize = $newSize;
      }
    }
    //-----------------------------------------------------------------------------------------------

    return $response;
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
        if ($request && $request != $this->currentRequest)
          throw new \RuntimeException ('NOT SUPPOSED TO HAPPEN?');

        $request  = $this->currentRequest = ($request ?: $this->currentRequest);
        $response = $this->currentResponse = ($response ?: $this->currentResponse);

        if ($first) $first = false;
        else $it->next ();
        if ($it->valid ()) {

          $routable = $it->current ();
          $pattern  = $it->key ();

          if (!$this->routingEnabled && !is_int ($pattern)) {

            // Route matching:

            if (!$this->matcher->match ($pattern, $request, $request))
              return $callNextHandler ();

            //-----------------------------------------------------------------------------------------------
            // DEBUG
            //-----------------------------------------------------------------------------------------------
            $this->routingLogger->write ("<#i|__rowHeader>Route pattern <b class=keyword>'$pattern'</b> matches request target <b class=keyword>'{$this->currentRequest->getRequestTarget()}'</b></#i>");
            //-----------------------------------------------------------------------------------------------
            // Proceed to handler invocation.
          }

          // Else, a middleware unconditional invocation will be performed.

          return $this->route ($routable, $request, $response, $callNextHandler);
        }
        // Iteration ended.

        //-----------------------------------------------------------------------------------------------
        // DEBUG
        //-----------------------------------------------------------------------------------------------
        $this->routingLogger->write ("</div><#i|__rowHeader>Pipeline ended</#i>");
        //-----------------------------------------------------------------------------------------------

        return $nextHandlerBeforeIter ($request, $response);
      };

    $it->rewind ();

    //-----------------------------------------------------------------------------------------------
    // DEBUG
    //-----------------------------------------------------------------------------------------------
    $this->routingLogger->write ("<#i|__rowHeader>Begin pipeline</#i>");

    if ($requestBeforeIter && $requestBeforeIter != $this->currentRequest) {
      $this->logRequest ($requestBeforeIter,
        sprintf ('with %s %s object:',
          $this->currentRequest ? 'a new' : 'the initial',
          Debug::getType ($requestBeforeIter))
      );
      $this->currentRequest     = $requestBeforeIter;
      $this->currentRequestSize = $requestBeforeIter->getBody ()->getSize ();
    }

    if ($responseBeforeIter && $responseBeforeIter != $this->currentResponse) {
      $this->logResponse ($responseBeforeIter,
        sprintf ('with %s %s object:',
          $this->currentResponse ? 'a new' : 'the initial',
          Debug::getType ($responseBeforeIter))
      );
      $this->currentResponse     = $responseBeforeIter;
      $this->currentResponseSize = $responseBeforeIter->getBody ()->getSize ();
    }

    $this->routingLogger->write ("<div class='indent'>");
    //-----------------------------------------------------------------------------------------------

    $r = $callNextHandler ($requestBeforeIter, $responseBeforeIter);

    //-----------------------------------------------------------------------------------------------
    // DEBUG
    //-----------------------------------------------------------------------------------------------
    $this->routingLogger->write ("</div>");
    $this->routingLogger->write ("<#i|__rowHeader>Exit pipeline</#i>");
    //-----------------------------------------------------------------------------------------------

    return $r;
  }

  /**
   * @param ServerRequestInterface $r
   * @param                        $title
   */
  private function logRequest ($r, $title)
  {
    $showAll = !$this->currentRequest;
    $icon    = $showAll ? '' : '<sup>*</sup>';
    $out     = [];
    if ($showAll || $r->getHeaders () != $this->currentRequest->getHeaders ())
      $out['Headers' . $icon] = map ($r->getHeaders (), function ($v) { return implode ('<br>', $v); });
    if ($showAll || $r->getAttributes () != $this->currentRequest->getAttributes ())
      $out['Attributes' . $icon] = $r->getAttributes ();
    if ($showAll || $r->getRequestTarget () != $this->currentRequest->getRequestTarget ())
      $out['Request target' . $icon] = $r->getRequestTarget ();
    if ($showAll || $r->getBody ()->getSize () != $this->currentRequestSize)
      $out['Size' . $icon] = $r->getBody ()->getSize ();

    $this->routingLogger
      ->write ("<div class='indent'>")
      ->simpleTable ($out, $title)
      ->write ('</div>');
  }

  /**
   * @param ResponseInterface $r
   * @param                   $title
   */
  private function logResponse ($r, $title)
  {
    $showAll = !$this->currentResponse;
    $icon    = $showAll ? '' : '<sup>*</sup>';
    $out     = [];
    if ($showAll || $r->getStatusCode () != $this->currentResponse->getStatusCode ())
      $out['Status' . $icon] = $r->getStatusCode () . ' ' . $r->getReasonPhrase ();
    $h = $r->getHeaders ();
    if ($showAll || $h != $this->currentResponse->getHeaders ())
      $out['Headers' . $icon] = map ($h, function ($v) { return implode ('<br>', $v); });
    if ($showAll || $r->getBody ()->getSize () != $this->currentResponseSize)
      $out['Size' . $icon] = $r->getBody ()->getSize ();

    $this->routingLogger
      ->write ('<div class=\'indent\'>')
      ->simpleTable ($out, $title)
      ->write ('</div>');
  }

}

