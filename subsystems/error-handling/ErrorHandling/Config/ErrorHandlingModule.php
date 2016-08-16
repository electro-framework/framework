<?php
namespace Electro\ErrorHandling\Config;

use Electro\ErrorHandling\Services\ErrorRenderer;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\ErrorRendererInterface;

class ErrorHandlingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (ErrorRendererInterface::class, ErrorRenderer::class)
      ->share (ErrorHandlingSettings::class);
  }

}
