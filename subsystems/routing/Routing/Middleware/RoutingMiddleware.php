<?php
namespace Selenia\Routing\Middleware;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;

/**
 *
 */
class RoutingMiddleware implements RequestHandlerInterface
{
  private $app;
  private $injector;

  function __construct (Application $app, InjectorInterface $injector)
  {
    $this->app      = $app;
    $this->injector = $injector;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($this->app->debugMode) {
      $rootR = implode ('', map ($this->app->routers, function ($r, $i) {
        ++$i;
        return sprintf ('<#i|__rowHeader><span>%d</span><#type>%s</#type></#i>', $i, get_class ($r));
      }));

      DebugConsole::logger('routes')
                  ->write ("<#section|Registered root routers>$rootR</#section><#section|Routables invoked while routing>");

      // Note: it is safe to not register this hidden panel.
      $routingLog           = new ConsoleLogger;
      $routingLog->hasPanel = false;
      DebugConsole::registerLogger ('routingLog', $routingLog);
    }

    /** @var RouterInterface $router */
    $router = $this->injector->make (RouterInterface::class);

    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));

    $res = $router
      ->set ($this->app->routers)
      ->__invoke ($request, $response, $next);

    DebugConsole::logger('routes')->write (DebugConsole::logger('routingLog')->getContent () . "</#section>");

    return $res;
  }

}
