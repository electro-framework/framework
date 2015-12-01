<?php
namespace Selenia\Routing\Lib\Debug;

use Iterator;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Routing\Lib\BaseRouter;
use Selenia\Routing\Lib\FactoryRoutable;
use Selenia\Routing\Services\RoutingLogger;

/**
 * Provides the inspection aspect of a RouterInterface implementation.
 * @property RouterInterface $decorated
 */
class BaseRouterWithLogging extends BaseRouter
{
  /**
   * The current request; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var ServerRequestInterface
   */
  static private $currentRequest;
  /**
   * The current request body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var int
   */
  static private $currentRequestSize = 0;
  /**
   * The current response; updated as the router calls request handlers.
   * @var ResponseInterface
   */
  static private $currentResponse;
  /**
   * The current response body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   * @var int
   */
  static private $currentResponseSize = 0;
  /**
   * @var bool
   */
  protected $debugMode;
  /**
   * @var RoutingLogger
   */
  protected $routingLogger;

  public function __construct (InjectorInterface $injector, RouteMatcherInterface $matcher,
                               RoutingLogger $routingLogger, $debugMode)
  {
    parent::__construct ($matcher, $injector);

    // Uncomment the following line if you want to see the routing log when the app crashes without the Debug Console
    // being displayed:
//    $routingLogger = new DirectOutputLogger();

    $this->routingLogger = $routingLogger;
    $this->debugMode     = $debugMode;
  }


  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Enter new Router</#i>");
    self::$currentRequestSize  = $request->getBody ()->getSize ();
    self::$currentResponseSize = $response->getBody ()->getSize ();
    try {
      return parent::__invoke ($request, $response, $next);
    }
    finally {
      $this->routingLogger->write ("<#i|__rowHeader>Exit Router</#i>");
    }
  }


  protected function callHandler (callable $handler, ServerRequestInterface $request, ResponseInterface $response,
                                  callable $next)
  {
    /** Router $this */
    $this->routingLogger
      ->write ("<#i|__rowHeader>Call ")->typeName ($handler)->write ("</#i>");

    if ($request && $request != self::$currentRequest) {
      $this->logRequest ($request, sprintf ('with a new %s object:', Debug::getType ($request)));
      self::$currentRequest     = $request;
      self::$currentRequestSize = $request->getBody ()->getSize ();
    }

    if ($response && $response != self::$currentResponse) {
      $this->logRequest ($request, sprintf ('with a new %s object:', Debug::getType ($response)));
      self::$currentResponse     = $response;
      self::$currentResponseSize = $response->getBody ()->getSize ();
    }


    $response = parent::callHandler ($handler, $request, $response, $next);


    $this->routingLogger->write ("<#i|__rowHeader>Return from ")->typeName ($handler)->write ('</#i>');

    if ($response !== self::$currentResponse) {
      $this->logResponse ($response, sprintf ('with a new %s object:', Debug::getType ($response)));
      self::$currentResponse     = $response;
      self::$currentResponseSize = $response->getBody ()->getSize ();
    }
    else {
      $newSize = $response->getBody ()->getSize ();
      if ($newSize != self::$currentResponseSize) {
        $this->logResponse ($response, sprintf ('with a modified %s body:', Debug::getType ($response)));
        self::$currentResponseSize = $newSize;
      }
    }

    return $response;
  }


  protected function iteration_start (\Iterator $it, ServerRequestInterface $currentRequest,
                                      ResponseInterface $currentResponse, callable $nextHandlerAfterIteration)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Begin pipeline</#i>");

    if ($currentRequest && $currentRequest != self::$currentRequest) {
      $this->logRequest ($currentRequest,
        sprintf ('with %s %s object:',
          self::$currentRequest ? 'a new' : 'the initial',
          Debug::getType ($currentRequest))
      );
      self::$currentRequest     = $currentRequest;
      self::$currentRequestSize = $currentRequest->getBody ()->getSize ();
    }

    if ($currentResponse && $currentResponse != self::$currentResponse) {
      $this->logResponse ($currentResponse,
        sprintf ('with %s %s object:',
          self::$currentResponse ? 'a new' : 'the initial',
          Debug::getType ($currentResponse))
      );
      self::$currentResponse     = $currentResponse;
      self::$currentResponseSize = $currentResponse->getBody ()->getSize ();
    }

    $this->routingLogger->write ("[@indent]");

    try {
      $finalResponse = parent::iteration_start ($it, $currentRequest, $currentResponse,
        $nextHandlerAfterIteration);

      return $finalResponse;
    }
    finally {
      $this->routingLogger->write ("[@/indent]");
      $this->routingLogger->write ("<#i|__rowHeader>Exit pipeline</#i>");
    }
  }


  protected function iteration_step ($key, $routable, ServerRequestInterface $request = null,
                                     ResponseInterface $response = null, callable $nextIteration)
  {
    if ($request && $request != self::$currentRequest)
      throw new \RuntimeException ('NOT SUPPOSED TO HAPPEN?');

    return parent::iteration_step ($key, $routable, $request, $response, $nextIteration);
  }


  protected function iteration_stepMatchRoute ($key, $routable, ServerRequestInterface $request,
                                               ResponseInterface $response, callable $nextIteration)
  {
    $this->routingLogger->write (sprintf ("<#i|__rowHeader>Route pattern <b class=keyword>'$key'</b> matches request target " .
                                          "<b class=keyword>'%s'</b></#i>",
      self::$currentRequest->getRequestTarget ()));

    return parent::iteration_stepMatchRoute ($key, $routable, $request, $response, $nextIteration);
  }


  protected function iteration_stop (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->routingLogger->write ("</div><#i|__rowHeader>Pipeline ended</#i>");

    return parent::iteration_stop ($request, $response, $next);
  }


  protected function runFactory (FactoryRoutable $factory)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Factory routable invoked</#i>");
    return parent::runFactory ($factory);
  }

  /**
   * @param ServerRequestInterface $r
   * @param                        $title
   */
  private function logRequest ($r, $title)
  {
    $showAll = !self::$currentRequest;
    $icon    = $showAll ? '' : '<sup>*</sup>';
    $out     = [];
    if ($showAll || $r->getHeaders () != self::$currentRequest->getHeaders ())
      $out['Headers' . $icon] = map ($r->getHeaders (), function ($v) { return implode ('<br>', $v); });
    if ($showAll || $r->getAttributes () != self::$currentRequest->getAttributes ())
      $out['Attributes' . $icon] = $r->getAttributes ();
    if ($showAll || $r->getRequestTarget () != self::$currentRequest->getRequestTarget ())
      $out['Request target' . $icon] = $r->getRequestTarget ();
    if ($showAll || $r->getBody ()->getSize () != self::$currentRequestSize)
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
    $showAll = !self::$currentResponse;
    $icon    = $showAll ? '' : '<sup>*</sup>';
    $out     = [];
    if ($showAll || $r->getStatusCode () != self::$currentResponse->getStatusCode ())
      $out['Status' . $icon] = $r->getStatusCode () . ' ' . $r->getReasonPhrase ();
    $h = $r->getHeaders ();
    if ($showAll || $h != self::$currentResponse->getHeaders ())
      $out['Headers' . $icon] = map ($h, function ($v) { return implode ('<br>', $v); });
    if ($showAll || $r->getBody ()->getSize () != self::$currentResponseSize)
      $out['Size' . $icon] = $r->getBody ()->getSize ();

    $this->routingLogger
      ->write ('<div class=\'indent\'>')
      ->simpleTable ($out, $title)
      ->write ('</div>');
  }

}
