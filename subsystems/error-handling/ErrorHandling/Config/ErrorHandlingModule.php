<?php
namespace Electro\ErrorHandling\Config;

use Electro\ErrorHandling\Services\ErrorRenderer;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Loader;

class ErrorHandlingModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader->onRegisterServices (function (InjectorInterface $injector) {
      $injector
        ->alias (ErrorRendererInterface::class, ErrorRenderer::class)
        ->share (ErrorHandlingSettings::class);
    });
  }

}
