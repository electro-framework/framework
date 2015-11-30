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
   * @var bool
   */
  protected $debugMode;
  /**
   * @var RoutingLogger
   */
  protected $routingLogger;
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
    $this->currentRequestSize  = $request->getBody ()->getSize ();
    $this->currentResponseSize = $response->getBody ()->getSize ();
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

    if ($handler instanceof $this)
      $handler->setPreviousContext ($this->currentRequest, $this->currentResponse);


    $response = parent::callHandler ($handler, $request, $response, $next);


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

    return $response;
  }


  protected function iteration_start (\Iterator $it, ServerRequestInterface $currentRequest,
                                      ResponseInterface $currentResponse, callable $nextHandlerAfterIteration)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Begin pipeline</#i>");

    if ($currentRequest && $currentRequest != $this->currentRequest) {
      $this->logRequest ($currentRequest,
        sprintf ('with %s %s object:',
          $this->currentRequest ? 'a new' : 'the initial',
          Debug::getType ($currentRequest))
      );
      $this->currentRequest     = $currentRequest;
      $this->currentRequestSize = $currentRequest->getBody ()->getSize ();
    }

    if ($currentResponse && $currentResponse != $this->currentResponse) {
      $this->logResponse ($currentResponse,
        sprintf ('with %s %s object:',
          $this->currentResponse ? 'a new' : 'the initial',
          Debug::getType ($currentResponse))
      );
      $this->currentResponse     = $currentResponse;
      $this->currentResponseSize = $currentResponse->getBody ()->getSize ();
    }

    $this->routingLogger->write ("<div class='indent'>");

    $finalResponse = parent::iteration_start ($it, $currentRequest, $currentResponse,
      $nextHandlerAfterIteration);

    $this->routingLogger->write ("</div>");
    $this->routingLogger->write ("<#i|__rowHeader>Exit pipeline</#i>");

    return $finalResponse;
  }


  protected function iteration_step ($key, $routable, ServerRequestInterface $request = null,
                                     ResponseInterface $response = null, callable $nextIteration)
  {
    if ($request && $request != $this->currentRequest)
      throw new \RuntimeException ('NOT SUPPOSED TO HAPPEN?');

    return parent::iteration_step ($key, $routable, $request, $response, $nextIteration);
  }


  protected function iteration_stepMatchRoute ($key, $routable, ServerRequestInterface $request,
                                               ResponseInterface $response, callable $nextIteration)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Route pattern <b class=keyword>'$key'</b> matches request target <b class=keyword>'{$this->currentRequest->getRequestTarget()}'</b></#i>");

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
   * Provides the router with information about the previous routing context.
   *
   * <p>The purpose is to provide enhanced debugging information on the Debug Console.
   *
   * @param ServerRequestInterface $prevRequest
   * @param ResponseInterface      $prevResponse
   */
  function setPreviousContext (ServerRequestInterface $prevRequest, ResponseInterface $prevResponse)
  {
    $this->routingLogger->write ("<#i|__rowHeader>Set debugging context</#i>");
    $this->currentRequest  = $prevRequest;
    $this->currentResponse = $prevResponse;
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
