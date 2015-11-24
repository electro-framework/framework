<?php
namespace Selenia\Routing\Middleware;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Routing\Services\RoutingLogger;

/**
 *
 */
class RoutingMiddleware implements RequestHandlerInterface
{
  private $app;
  private $injector;
  /**
   * @var RouterInterface
   */
  private $router;
  /**
   * @var RoutingLogger
   */
  private $routingLogger;

  function __construct (RouterInterface $router, Application $app, InjectorInterface $injector,
                        RoutingLogger $routingLogger)
  {
    $this->router        = $router;
    $this->app           = $app;
    $this->injector      = $injector;
    $this->routingLogger = $routingLogger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($this->app->debugMode) {
      $rootR = implode ('', map ($this->app->routers, function ($r, $i) {
        ++$i;
        return sprintf ('<#i|__rowHeader><#type>%s</#type></#i>', get_class ($r));
      }));

      DebugConsole::logger ('routes')
                  ->write ("<#section|Registered root routers>$rootR</#section>" .
                           "<#section|Routables invoked while routing>" .
                           "<#i|__rowHeader><i>(previous middlewares are omitted because they can't be traced)</i></#i>");
    }

    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));
    $res     = $this->router
      ->set ($this->app->routers)
      ->__invoke ($request, $response, $next);

    DebugConsole::logger ('routes')->write ($this->routingLogger->getContent () . "</#section>");

    return $res;
  }

}
