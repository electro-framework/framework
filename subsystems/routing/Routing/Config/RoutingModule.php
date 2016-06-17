<?php
namespace Electro\Routing\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Routing\Middleware\RoutingMiddleware;
use Electro\Routing\Services\Debug\MiddlewareStackWithLogging;
use Electro\Routing\Services\Debug\RouterWithLogging;
use Electro\Routing\Services\MiddlewareStack;
use Electro\Routing\Services\RouteMatcher;
use Electro\Routing\Services\Router;
use Electro\Routing\Services\RoutingLogger;

class RoutingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector->execute (function ($debugConsole) use ($injector) {

      $injector
        //
        // Routing / Middleware classes
        //
        ->alias (RouterInterface::class,
          $debugConsole ? RouterWithLogging::class : Router::class)
        ->alias (MiddlewareStackInterface::class,
          $debugConsole ? MiddlewareStackWithLogging::class : MiddlewareStack::class)
        ->alias (RouteMatcherInterface::class, RouteMatcher::class)
        //
        // The application's root/main router
        // (inject it to add routes to it)
        //
        ->share (ApplicationRouterInterface::class)
        ->alias (ApplicationRouterInterface::class, RoutingMiddleware::class)
        //
        // The application's root/main middleware stack
        //
        ->share (ApplicationMiddlewareInterface::class)
        ->alias (ApplicationMiddlewareInterface::class,
          $debugConsole ? MiddlewareStackWithLogging::class : MiddlewareStack::class);

      if ($debugConsole) $injector
        ->share (RoutingLogger::class);
    });

  }

}
