<?php
namespace Selenia\Routing\Middleware;
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\WebConsole;
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

      WebConsole::routes ()
                ->write ("<#section|Registered root routers>$rootR</#section><#section|Routables invoked while routing>");
    }
    $routingLog          = new ConsolePanel;
    $routingLog->visible = false;
    WebConsole::registerPanel ('routingLog', $routingLog);

    /** @var RouterInterface $router */
    $router = $this->injector->make (RouterInterface::class);

    $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));

    $res = $router
      ->set ($this->app->routers)
      ->__invoke ($request, $response, $next);

    WebConsole::routes ()->write (WebConsole::routingLog ()->getContent () . "</#section>");

    return $res;
  }

}
