<?php
namespace Electro\Routing\Lib\Debug;

use Iterator;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Routing\Lib\BaseRouter;
use Electro\Routing\Lib\FactoryRoutable;
use Electro\Routing\Services\RoutingLogger;

/**
 * Provides the inspection aspect of a RouterInterface implementation.
 *
 * @property RouterInterface $decorated
 */
class BaseRouterWithLogging extends BaseRouter
{
  /**
   * The current request; updated as the router calls request handlers.
   * > This is used for debugging only.
   *
   * @var ServerRequestInterface
   */
  static private $currentRequest;
  /**
   * The current request body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   *
   * @var int
   */
  static private $currentRequestSize = 0;
  /**
   * The current response; updated as the router calls request handlers.
   *
   * @var ResponseInterface
   */
  static private $currentResponse;
  /**
   * The current response body size; updated as the router calls request handlers.
   * > This is used for debugging only.
   *
   * @var int
   */
  static private $currentResponseSize = 0;
  /**
   * Are we currently unwinding the handler stacks due to a thrown exception?
   *
   * @var bool
   */
  static private $unwinding = false;

  /**
   * @var bool
   */
  protected $debugMode;
  /**
   * @var RoutingLogger
   */
  protected $routingLogger;

  public function __construct (InjectorInterface $injector,
                               RouteMatcherInterface $matcher,
                               RoutingLogger $routingLogger, $debugMode)
  {
    parent::__construct ($matcher, $injector);

    // Uncomment the following line if you want to see the routing log when the app crashes without the Debug Console
    // being displayed:
    //
    // $routingLogger = new DirectOutputLogger();

    $this->routingLogger = $routingLogger;
    $this->debugMode     = $debugMode;
  }


  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->routingLogger->write ("<#row>Enter new Router</#row>");
    self::$currentRequestSize  = $request->getBody ()->getSize ();
    self::$currentResponseSize = $response->getBody ()->getSize ();
    try {
      return parent::__invoke ($request, $response, $next);
    }
    finally {
      $this->routingLogger->writef ("<#row>Exit %s</#row>", Debug::getType ($this));
    }
  }


  protected function callHandler (callable $handler, ServerRequestInterface $request, ResponseInterface $response,
                                  callable $next)
  {
    /** Router $this */
    $this->routingLogger
      ->writef ("<#row>Call %s</#row>", Debug::getType ($handler));

    DebugConsole::logger ('request')->setRequest ($request);

    if ($request && $request != self::$currentRequest) {
      $this->logRequest ($request, sprintf ('with another %s object:', Debug::getType ($request)));
      self::$currentRequest     = $request;
      self::$currentRequestSize = $request->getBody ()->getSize ();
    }

    if ($response && $response != self::$currentResponse) {
      $this->logRequest ($request, sprintf ('with a new %s object:', Debug::getType ($response)));
      self::$currentResponse     = $response;
      self::$currentResponseSize = $response->getBody ()->getSize ();
    }


    $response = parent::callHandler ($handler, $request, $response, $next);


    $this->routingLogger->writef ("<#row>Return from %s</#row>", Debug::getType ($handler));

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
                                      ResponseInterface $currentResponse, callable $nextHandlerAfterIteration,
                                      $stackId)
  {
    $this->routingLogger->writef ("<#row>Begin stack %d</#row>", $stackId);

    if ($currentRequest && $currentRequest != self::$currentRequest) {
      if (!self::$currentRequest) {
        $this->routingLogger
          ->writef ("<#indent><table class=\"__console-table with-caption\"><caption>with the initial %s object &nbsp; <a class='fa fa-external-link' href='javascript:openConsoleTab(\"request\")'></a></caption></table></#indent>",
            Debug::getType ($currentRequest));
      }
      else $this->logRequest ($currentRequest,
        sprintf ('with another %s object:', Debug::getType ($currentRequest))
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

    $this->routingLogger->write ("<#indent>");

    try {
      $finalResponse = parent::iteration_start ($it, $currentRequest, $currentResponse,
        $nextHandlerAfterIteration, $stackId);

      return $finalResponse;
    }
    catch (\Throwable $e) {
      $this->unwind ($e);
    }
    catch (\Exception $e) {
      $this->unwind ($e);
    }
    finally {
      $this->routingLogger->writef ("</#indent><#row>Exit stack %d</#row>", $stackId);
    }
  }

  protected function iteration_step ($key, $routable, ServerRequestInterface $request = null,
                                     ResponseInterface $response = null, callable $nextIteration)
  {
    if ($request && $request != self::$currentRequest) //NOT SUPPOSED TO HAPPEN?
      self::$currentRequest = $request;

    return parent::iteration_step ($key, $routable, $request, $response, $nextIteration);
  }

  protected function iteration_stepMatchRoute ($key, $routable, ServerRequestInterface $request,
                                               ResponseInterface $response, callable $nextIteration)
  {
    $this->routingLogger->writef ("<#row><b class=keyword>'%s'</b> <b>matches</b> route pattern <b class=keyword>'$key'</b></#row>",
      self::$currentRequest->getRequestTarget ());

    return parent::iteration_stepMatchRoute ($key, $routable, $request, $response, $nextIteration);
  }

  protected function iteration_stepNotMatchRoute ($key, $routable, ServerRequestInterface $request,
                                                  ResponseInterface $response, callable $nextIteration)
  {
    $this->routingLogger->writef ("<#row><b class=keyword>'%s'</b> doesn't match route pattern <b class=keyword>'$key'</b></#row>",
      self::$currentRequest->getRequestTarget ());

    return parent::iteration_stepNotMatchRoute ($key, $routable, $request, $response, $nextIteration);
  }

  protected function iteration_stop (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->routingLogger->writef ("<#row>End of stack %d</#row>", $this->stackId);

    return parent::iteration_stop ($request, $response, $next);
  }

  protected function runFactory (FactoryRoutable $factory)
  {
    $this->routingLogger->write ("<#row>Factory routable invoked</#row>");
    return parent::runFactory ($factory);
  }

  /**
   * @param ServerRequestInterface $r
   * @param                        $title
   */
  private function logRequest ($r, $title, $forceShow = false)
  {
    $showAll = !self::$currentRequest || $forceShow;
    $icon    = $showAll ? '' : '<sup>*</sup>';
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

  private function unwind ($e)
  {
    $this->routingLogger->writef ("<#row>%sUnwinding the stack...</#row>",
      self::$unwinding ? '' : '<span class=__alert>' . Debug::getType ($e) . '</span> ');
    self::$unwinding = true;
    throw $e;
  }

}
