<?php
namespace Electro\Routing\Lib\Debug;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Routing\Lib\BaseRouter;
use Electro\Routing\Services\RoutingLogger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Lib\Debug;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides the inspection aspect of a RouterInterface implementation.
 *
 * @property RouterInterface $decorated
 */
class BaseRouterWithLogging extends BaseRouter
{
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
  protected $devEnv;
  /**
   * @var RoutingLogger
   */
  protected $routingLogger;

  public function __construct (InjectorInterface $injector,
                               RouteMatcherInterface $matcher,
                               RoutingLogger $routingLogger,
                               CurrentRequestInterface $currentRequestMutator,
                               DebugSettings $debugSettings)
  {
    parent::__construct ($matcher, $injector, $currentRequestMutator);

    // Uncomment the following line if you want to see the routing log when the app crashes without the Debug Console
    // being displayed:
    //
    // $routingLogger = new DirectOutputLogger();

    $this->routingLogger = $routingLogger;
    $this->devEnv        = $debugSettings->devEnv;
  }


  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $type = Debug::shortenType (get_parent_class ($this));
    $this->routingLogger->writef ("<#row>Enter %s</#row>", $type);
    self::$currentRequestSize  = $request->getBody ()->getSize ();
    self::$currentResponseSize = $response->getBody ()->getSize ();
    try {
      return parent::__invoke ($request, $response, $next);
    }
    finally {
      $this->routingLogger->writef ("<#row>Exit %s</#row>", $type);
    }
  }


  protected function callHandler (callable $handler, ServerRequestInterface $request, ResponseInterface $response,
                                  callable $next)
  {
    /** Router $this */

    $log = DebugConsole::logger ('request');
    if ($log instanceof PSR7RequestLogger)
      $log->setRequest ($request);

    if ($request && $request != $this->currentRequest->getInstance ()) {
      $this->logRequest ($request, sprintf ('with another %s object:', Debug::getType ($request)));
//      $this->currentRequestMutator->set ($request); // DO NOT DO THIS HERE; IT WILL BE DONE ON THE PARENT.
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

  protected function handleIterableRoutable (\Iterator $it, ServerRequestInterface $currentRequest,
                                             ResponseInterface $currentResponse, callable $next)
  {
    $this->routingLogger->writef ("<#row>Start %s</#row>", $this->routingEnabled ? 'routing node' : 'middleware stack');

    if ($currentRequest && $currentRequest != $this->currentRequest->getInstance ()) {
      if (!$this->currentRequest->getInstance ()) {
        $this->routingLogger
          ->writef ("<#indent><table class=\"__console-table with-caption\"><caption>with the initial %s object &nbsp; <a class='fa fa-external-link' href='javascript:openConsoleTab(\"request\")'></a></caption></table></#indent>",
            Debug::getType ($currentRequest));
      }
      else $this->logRequest ($currentRequest,
        sprintf ('with another %s object:', Debug::getType ($currentRequest))
      );
      $this->currentRequest->setInstance ($currentRequest);
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
      $finalResponse = parent::handleIterableRoutable ($it, $currentRequest, $currentResponse, $next);

      return $finalResponse;
    }
    catch (\Throwable $e) {
      $this->unwind ($e);
    }
    catch (\Exception $e) {
      $this->unwind ($e);
    }
    finally {
      $this->routingLogger->write ("</#indent>");
    }
  }

  protected function match_patterns (array $patterns, ServerRequestInterface $request,
                                     ResponseInterface $response)
  {
    $this->routingLogger->writef ("<#row>Matching URL path <b class=keyword>'%s'</b>...</#row>",
      $this->currentRequest->getInstance ()->getRequestTarget ());
    return parent::  match_patterns ($patterns, $request, $response);
  }

  protected function iteration_step ($key, $routable, ServerRequestInterface $request = null,
                                     ResponseInterface $response = null)
  {
    if ($request && $request != $this->currentRequest->getInstance ()) //NOT SUPPOSED TO HAPPEN?
      $this->currentRequest->setInstance ($request);

    return parent::iteration_step ($key, $routable, $request, $response);
  }

  protected function iteration_stepMatchMiddleware ($key, $routable, ServerRequestInterface $request,
                                                    ResponseInterface $response, callable $nextIteration)
  {
    $t = is_string ($routable) ? Debug::shortenType ($routable) : Debug::getType ($routable);
    $this->routingLogger
      ->write ("<#row>Call #<b>$key</b>: $t</#row>");
    return parent::iteration_stepMatchMiddleware ($key, $routable, $request, $response, $nextIteration);
  }

  protected function iteration_stepMatchRoute ($key, $routable, ServerRequestInterface $request,
                                               ResponseInterface $response)
  {
    $this->routingLogger->write ("<#row>Route pattern <b class=keyword>'$key'</b> <b style='color:green'>MATCHES</b></#row>");

    return parent::iteration_stepMatchRoute ($key, $routable, $request, $response);
  }

  protected function iteration_stepNotMatchRoute ($key, $routable, ServerRequestInterface $request,
                                                  ResponseInterface $response)
  {
    $this->routingLogger->write ("<#row>Route pattern <b class=keyword>'$key'</b> doesn't match</#row>");

    parent::iteration_stepNotMatchRoute ($key, $routable, $request, $response);
  }

  protected function iteration_stop (ServerRequestInterface $request, ResponseInterface $response = null)
  {
    $this->routingLogger->writef ("</#indent><#row>Finish %s</#row>",
      $this->routingEnabled ? 'routing node' : 'middleware stack');
  }

  /**
   * @param ServerRequestInterface $r
   * @param                        $title
   */
  private function logRequest ($r, $title, $forceShow = false)
  {
    /** @var ServerRequestInterface $current */
    $current = $this->currentRequest->getInstance ();
    $showAll = !$this->currentRequest->getInstance () || $forceShow;
    $icon    = $showAll ? '' : '<sup>*</sup>';
    if ($showAll || $r->getHeaders () != $current->getHeaders ())
      $out['Headers' . $icon] = map ($r->getHeaders (), function ($v) { return implode ('<br>', $v); });
    if ($showAll || $r->getAttributes () != $current->getAttributes ())
      $out['Attributes' . $icon] = $r->getAttributes ();
    if ($showAll || $r->getRequestTarget () != $current->getRequestTarget ())
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
