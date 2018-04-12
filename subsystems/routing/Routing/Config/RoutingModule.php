<?php
namespace Electro\Routing\Config;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\MiddlewareAssemblerInterface;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ApiProfile;
use Electro\Profiles\WebProfile;
use Electro\Routing\Middleware\RoutingMiddleware;
use Electro\Routing\Services\Debug\MiddlewareStackWithLogging;
use Electro\Routing\Services\Debug\RouterWithLogging;
use Electro\Routing\Services\MiddlewareStack;
use Electro\Routing\Services\RouteMatcher;
use Electro\Routing\Services\Router;
use Electro\Routing\Services\RoutingLogger;

class RoutingModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ApiProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector->execute (function (DebugSettings $debugSettings) use ($injector) {
          $injector
            //
            // Routing / Middleware classes
            //
            ->alias (RouterInterface::class,
              // $debugSettings->webConsole ? RouterWithLogging::class : Router::class)
              $debugSettings->webConsole ? Router::class : Router::class)
            ->alias (MiddlewareStackInterface::class,
              // $debugSettings->webConsole ? MiddlewareStackWithLogging::class : MiddlewareStack::class)
              $debugSettings->webConsole ? MiddlewareStack::class : MiddlewareStack::class)
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
            ->delegate (ApplicationMiddlewareInterface::class,
              function (MiddlewareAssemblerInterface $assembler, MiddlewareStackInterface $stack) {
                $assembler->assemble ($stack);
                return $stack;
              });

          if ($debugSettings->webConsole)
            $injector
              ->share (RoutingLogger::class);
        });

      });
  }

}
