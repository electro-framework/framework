<?php
namespace Selenia\ErrorHandling\Config;

use Selenia\ErrorHandling\Services\ErrorRenderer;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\Http\ErrorRendererInterface;
use Zend\Diactoros\Response;

class ErrorHandlingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (ErrorRendererInterface::class, ErrorRenderer::class)
      ->share (ErrorHandlingSettings::class);
  }

}
