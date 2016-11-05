<?php
namespace Electro\WebServer\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\MiddlewareAssemblerInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\WebServer\DefaultMiddlewareAssembler;
use Electro\WebServer\WebServer;

class WebServerModule implements ModuleInterface
{
  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel
      ->onRegisterServices (
        function (InjectorInterface $injector) {
          $injector
            ->share (WebServer::class)
            // The middleware assembler may be overridden later, usually by a private application module.
            ->alias (MiddlewareAssemblerInterface::class, DefaultMiddlewareAssembler::class);
        })
      // Create the PSR-7 ServerRequest
      ->onConfigure (function (WebServer $webServer) {
        $webServer->setup ();
      })
      //
      ->onRun (function (WebServer $webServer) {
        $webServer->run ();
      });
  }

}
