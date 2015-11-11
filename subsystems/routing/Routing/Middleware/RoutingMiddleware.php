<?php
namespace Selenia\Routing\Middleware;
use PhpKit\WebConsole\WebConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Routing\Router;

/**
 *
 */
class RoutingMiddleware implements MiddlewareInterface
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
    $this->loadRoutes ();

    if ($this->app->debugMode) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withFilter ($filter, $this->app->routingMap->routes);
    }

    $router = new Router();
    $this->injector->share ($router);

    return $router->route () ?: $next ();
  }

}

//if ($response) {
//  /** @var PageComponent $controller */
//  $controller         = $this->injector->make ($controllerClass);
//  $router->controller = $controller;
//  $controller->router = $router;
//
//  return $controller->__invoke ($request, $response, $next);
//}
