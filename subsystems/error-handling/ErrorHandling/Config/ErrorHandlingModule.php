<?php
namespace Electro\ErrorHandling\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\ErrorHandling\Services\ErrorRenderer;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\ModuleInterface;

class ErrorHandlingModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector) {
      $injector
        ->alias (ErrorRendererInterface::class, ErrorRenderer::class)
        ->share (ErrorHandlingSettings::class);
    });
  }

}
