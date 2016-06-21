<?php
namespace Electro\ViewEngine\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\ViewEngine\Lib\View;
use Electro\ViewEngine\Services\AssetsService;
use Electro\ViewEngine\Services\BlocksService;
use Electro\ViewEngine\Services\ViewService;

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
        // No default view engines are available at this time.
      })
      ->share (AssetsService::class)
      ->share (BlocksService::class);
  }

}
