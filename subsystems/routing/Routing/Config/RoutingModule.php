<?php
namespace Selenia\Routing\Config;

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
use Selenia\Routing\MiddlewareStack;
use Selenia\Routing\Navigation\Navigation;
use Selenia\Routing\Navigation\NavigationLink;
use Selenia\Routing\RouteMatcher;
use Selenia\Routing\Router;
use Selenia\Routing\Services\RoutingLogger;

class RoutingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      //
      // Routing
      //
      ->alias (RouterInterface::class, Router::class)
      ->alias (MiddlewareStackInterface::class, MiddlewareStack::class)
      ->alias (RouteMatcherInterface::class, RouteMatcher::class)
      ->share (RoutingLogger::class)
      //
      // The application's root/main router
      // (inject it to add routes to it)
      //
      ->share (RootRouterInterface::class)
      ->alias (RootRouterInterface::class, RoutingMiddleware::class)
      //
      // The application's root/main middleware stack
      //
      ->share (RootMiddlewareStackInterface::class)
      ->alias (RootMiddlewareStackInterface::class, MiddlewareStack::class)
      //
      // Navigation
      //
      ->alias (NavigationInterface::class, Navigation::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
