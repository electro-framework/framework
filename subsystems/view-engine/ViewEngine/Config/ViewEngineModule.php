<?php
namespace Electro\ViewEngine\Config;

use Electro\Caching\Lib\FileSystemCache;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\WebProfile;
use Electro\ViewEngine\Lib\TemplateCache;
use Electro\ViewEngine\Lib\View;
use Electro\ViewEngine\Services\AssetsService;
use Electro\ViewEngine\Services\BlocksService;
use Electro\ViewEngine\Services\ViewService;

class ViewEngineModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) use ($kernel) {
        $injector
          ->alias (ViewInterface::class, View::class)//note: this is not used by ViewService.
          ->alias (ViewServiceInterface::class, ViewService::class)
          ->share (ViewServiceInterface::class)
          // Register the built-in view engines:
          ->prepare (ViewServiceInterface::class, function (ViewServiceInterface $viewService) {
            // No default view engines are available at this time.
          })
          ->share (AssetsService::class)
          ->share (BlocksService::class)
          ->share (ViewEngineSettings::class)
          ->delegate (TemplateCache::class, function (ViewEngineSettings $settings) use ($injector, $kernel) {
            return new TemplateCache(
              $injector->make (FileSystemCache::class),
              $settings->caching (),
              $kernel->devEnv ()
            );
          })
          ->share (TemplateCache::class)
          ->alias (ViewModelInterface::class, ViewModel::class);
      });
  }

}
