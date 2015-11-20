<?php
namespace Selenia\Routing\Middleware;
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
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withFilter ($filter, $this->app->routers);
    }

    /** @var RouterInterface $router */
    $router = $this->injector->make (RouterInterface::class);

    $request = $request->withRequestTarget ($request->getAttribute('virtualUri'));

    return $router
      ->set ($this->app->routers)
      ->__invoke ($request, $response, $next);
  }

}
