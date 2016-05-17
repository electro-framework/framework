<?php
namespace Selenia\Routing\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\Http\MiddlewareStackInterface;
use Selenia\Interfaces\Http\RouteMatcherInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Selenia\Interfaces\Http\Shared\ApplicationRouterInterface;
use Selenia\Routing\Middleware\RoutingMiddleware;
use Selenia\Routing\Services\Debug\MiddlewareStackWithLogging;
use Selenia\Routing\Services\Debug\RouterWithLogging;
use Selenia\Routing\Services\MiddlewareStack;
use Selenia\Routing\Services\RouteMatcher;
use Selenia\Routing\Services\Router;
use Selenia\Routing\Services\RoutingLogger;

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
