<?php
namespace Selenia\Sessions\Config;

use Selenia\Interfaces\Http\RequestHandlerPipelineInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\RouteMatcherInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Routing\RouteMatcher;
use Selenia\Routing\Router;

class RoutingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (RouterInterface::class, Router::class)
      ->alias (RequestHandlerPipelineInterface::class, Router::class)
      ->prepare (RequestHandlerPipelineInterface::class, function (Router $router) {
        $router->routingEnabled = false;
      })
      ->alias (RouteMatcherInterface::class, RouteMatcher::class);
  }

}
