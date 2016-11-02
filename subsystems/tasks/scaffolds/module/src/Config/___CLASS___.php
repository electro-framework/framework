<?php
namespace ___NAMESPACE___\Config;

use Electro\Application;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Plugins\Matisse\Config\MatisseSettings;
use Electro\ViewEngine\Config\ViewEngineSettings;
use const Electro\Core\Assembly\Services\CONFIGURE;

class ___CLASS___ implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper
      //
      ->on (CONFIGURE,
        function (MatisseSettings $matisseSettings, Application $app, ApplicationRouterInterface $router,
                  NavigationSettings $navigationSettings, ViewEngineSettings $viewEngineSettings)
        use ($moduleInfo) {
          $app->name    = 'yourapp';      // session cookie name
          $app->appName = 'Your App';     // default page title; also displayed on title bar (optional)
          $app->title   = '@ - Your App'; // @ = page title
          $matisseSettings->registerMacros ($moduleInfo);
          $viewEngineSettings->registerViews ($moduleInfo);
          $router->add (Routes::class);
          $navigationSettings->registerNavigation (Navigation::class);
        });
  }

}
