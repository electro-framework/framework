<?php
namespace Electro\Navigation\Config;

use Electro\Application;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Navigation\Lib\NavigationLink;
use Electro\Navigation\Services\Navigation;

class NavigationModule implements ServiceProviderInterface, ModuleInterface
{
  /** @var InjectorInterface */
  private $injector;

  function boot (Application $app, NavigationInterface $navigation)
  {
    foreach ($app->navigationProviders as $provider) {
      if (is_string ($provider))
        $provider = $this->injector->make ($provider);
      $provider->defineNavigation ($navigation);
    }
  }

  function register (InjectorInterface $injector)
  {
    $this->injector = $injector;
    $injector
      ->alias (NavigationInterface::class, Navigation::class)
      ->share (NavigationInterface::class, 'navigation')
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }
}
