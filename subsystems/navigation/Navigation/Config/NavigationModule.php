<?php
namespace Selenia\Navigation\Config;

use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Navigation\Lib\NavigationLink;
use Selenia\Navigation\Services\Navigation;

class NavigationModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    /** @var Application $app */
    $app = $injector->make (Application::class);
    $injector
      ->share (NavigationInterface::class)
      ->alias (NavigationInterface::class, Navigation::class)
      ->prepare (Navigation::class, function (Navigation $navigation) use ($app) {
        foreach ($app->navigationProviders as $provider)
          $provider->defineNavigation ($navigation);
      })
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
