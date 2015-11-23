<?php
namespace Selenia\Routing\Config;

use Selenia\Interfaces\Http\RequestHandlerPipelineInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\ServiceProviderInterface;
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
      // Routing:
      ->alias (RouterInterface::class, Router::class)
      ->alias (RouteMatcherInterface::class, RouteMatcher::class)
      ->share (RoutingLogger::class)
      // The application's middleware pipeline:
      ->alias (RequestHandlerPipelineInterface::class, Router::class)
      ->share (RequestHandlerPipelineInterface::class)
      ->prepare (RequestHandlerPipelineInterface::class, function (Router $router) {
        $router->routingEnabled = false;
      })
      // Navigation:
      ->alias (NavigationInterface::class, Navigation::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
