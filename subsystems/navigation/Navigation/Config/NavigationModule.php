<?php
namespace Selenia\Navigation\Config;

use Selenia\Application;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Navigation\Lib\NavigationLink;
use Selenia\Navigation\Services\Navigation;

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
      ->share (NavigationInterface::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
