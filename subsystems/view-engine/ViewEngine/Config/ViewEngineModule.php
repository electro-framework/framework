<?php
namespace Selenia\ViewEngine\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\ViewEngine\Engines\MatisseEngine;
use Selenia\ViewEngine\Lib\View;
use Selenia\ViewEngine\Services\AssetsService;
use Selenia\ViewEngine\Services\BlocksService;
use Selenia\ViewEngine\Services\ViewService;

class ViewEngineModule implements ServiceProviderInterface, ModuleInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (ViewInterface::class, View::class)//note: this is not used by ViewService.
      ->alias (ViewServiceInterface::class, ViewService::class)
      ->share (ViewServiceInterface::class)
      // Register the built-in view engines:
      ->prepare (ViewServiceInterface::class, function (ViewServiceInterface $viewService) {
        $viewService->register (MatisseEngine::class, '/\.html$/');
      })
      ->share (AssetsService::class)
      ->share (BlocksService::class);
  }

}
