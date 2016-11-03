<?php
namespace Electro\Navigation\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Bootstrapper;
use Electro\Navigation\Lib\NavigationLink;
use Electro\Navigation\Services\Navigation;
use const Electro\Kernel\Services\RECONFIGURE;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class NavigationModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper
      //
      ->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
        $injector
          ->alias (NavigationInterface::class, Navigation::class)
          ->share (NavigationInterface::class, 'navigation')
          ->alias (NavigationLinkInterface::class, NavigationLink::class)
          ->share (NavigationSettings::class);
      })
      //
      ->on (RECONFIGURE,
        function (InjectorInterface $injector, NavigationSettings $settings, NavigationInterface $navigation) {
          foreach ($settings->getProviders () as $provider) {
            if (is_string ($provider))
              $provider = $injector->make ($provider);
            $provider->defineNavigation ($navigation);
          }
        });
  }

}
