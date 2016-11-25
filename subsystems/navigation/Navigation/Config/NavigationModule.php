<?php
namespace Electro\Navigation\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Navigation\Lib\NavigationLink;
use Electro\Navigation\Services\Navigation;
use Electro\Profiles\WebProfile;

class NavigationModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel
      ->onRegisterServices (
        function (InjectorInterface $injector) {
          $injector
            ->alias (NavigationInterface::class, Navigation::class)
            ->share (NavigationInterface::class, 'navigation')
            ->alias (NavigationLinkInterface::class, NavigationLink::class)
            ->share (NavigationSettings::class);
        })
      //
      ->onReconfigure (
        function (InjectorInterface $injector, NavigationSettings $settings, NavigationInterface $navigation) {
          foreach ($settings->getProviders () as $provider) {
            if (is_string ($provider))
              $provider = $injector->make ($provider);
            $provider->defineNavigation ($navigation);
          }
        });
  }

}
