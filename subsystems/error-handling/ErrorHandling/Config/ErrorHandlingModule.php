<?php
namespace Selenia\ErrorHandling\Config;

use Selenia\ErrorHandling\Services\ErrorRenderer;
use Selenia\Interfaces\Http\ErrorRendererInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
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
