<?php
namespace Electro\Navigation\Config;

use Electro\Application;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Navigation\Lib\NavigationLink;
use Electro\Navigation\Services\Navigation;

class NavigationModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->delegate (NavigationInterface::class, function (Application $app) {
        $navigation = new Navigation;
        foreach ($app->navigationProviders as $provider)
          $provider->defineNavigation ($navigation);
        return $navigation;
      })
      ->share (NavigationInterface::class, 'navigation')
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
