<?php
namespace Selenia\Routing\Middleware;
use PhpKit\WebConsole\WebConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Http\Controllers\Controller;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Routing\Router;
use Selenia\Routing\RoutingMap;

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
    $router->init ();

    $controllerClass = $router->route ();
    if ($controllerClass) {
      /** @var Controller $controller */
      $controller         = $this->injector->make ($controllerClass);
      $router->controller = $controller;
      $controller->router = $router;

      //TODO: refactor this
      /** @var SessionInterface $session */
      $session = $this->injector->make ('Selenia\Interfaces\SessionInterface');
      $controller->flashMessage = $session->getFlashMessage();


      return $controller->__invoke ($request, $response, $next);
    }
    return $next ();
  }

  private function loadRoutes ()
  {
    $map         = $this->app->routingMap = new RoutingMap;
    $map->routes = array_merge ($map->routes, $this->app->routes);
    $map->init ();
  }

}
