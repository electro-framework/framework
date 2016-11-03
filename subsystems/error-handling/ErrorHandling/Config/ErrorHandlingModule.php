<?php
namespace Electro\ErrorHandling\Config;

use Electro\ErrorHandling\Services\ErrorRenderer;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Bootstrapper;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class ErrorHandlingModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->alias (ErrorRendererInterface::class, ErrorRenderer::class)
        ->share (ErrorHandlingSettings::class);
    });
  }

}
