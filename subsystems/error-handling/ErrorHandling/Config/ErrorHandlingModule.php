<?php
namespace Electro\ErrorHandling\Config;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\ErrorHandling\Services\ErrorRenderer;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\ModuleInterface;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

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
