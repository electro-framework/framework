<?php
namespace Selenia\Routing\Config;

use Selenia\Application;
use Selenia\Interfaces\Http\MiddlewareStackInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\Http\Shared\RootMiddlewareStackInterface;
use Selenia\Interfaces\Http\Shared\RootRouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Routing\Middleware\RoutingMiddleware;
use Selenia\Routing\Navigation\Navigation;
use Selenia\Routing\Navigation\NavigationLink;
use Selenia\Routing\Services\Debug\MiddlewareStackWithLogging;
use Selenia\Routing\Services\Debug\RouterWithLogging;
use Selenia\Routing\Services\Debug\RoutingMiddlewareWithLogging;
use Selenia\Routing\Services\MiddlewareStack;
use Selenia\Routing\Services\RouteMatcher;
use Selenia\Routing\Services\Router;
use Selenia\Routing\Services\RoutingLogger;

class RoutingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    /** @var Application $app */
    $app       = $injector->make (Application::class);
    $debugMode = $app->debugMode;
    $injector
      //
      // Routing
      //
      ->alias (RouterInterface::class, $debugMode ? RouterWithLogging::class : Router::class)
      ->alias (MiddlewareStackInterface::class, $debugMode ? MiddlewareStackWithLogging::class : MiddlewareStack::class)
      ->alias (RouteMatcherInterface::class, RouteMatcher::class)
      //
      // The application's root/main router
      // (inject it to add routes to it)
      //
      ->share (RootRouterInterface::class)
      ->alias (RootRouterInterface::class, $debugMode ? RoutingMiddlewareWithLogging::class : RoutingMiddleware::class)
      //
      // The application's root/main middleware stack
      //
      ->share (RootMiddlewareStackInterface::class)
      ->alias (RootMiddlewareStackInterface::class,
        $debugMode ? MiddlewareStackWithLogging::class : MiddlewareStack::class)
      //
      // Navigation
      //
      ->alias (NavigationInterface::class, Navigation::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);

    if ($debugMode) $injector
      ->share (RoutingLogger::class);
  }

}
