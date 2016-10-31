<?php
namespace Electro\Navigation\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Navigation\Lib\NavigationLink;
use Electro\Navigation\Services\Navigation;

class NavigationModule implements ModuleInterface
{
  /** @var InjectorInterface */
  private $injector;

  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector) {
      $injector
        ->alias (NavigationInterface::class, Navigation::class)
        ->share (NavigationInterface::class, 'navigation')
        ->alias (NavigationLinkInterface::class, NavigationLink::class);
    });

    $boot->on (Bootstrapper::EVENT_POST_BOOT, function (Application $app, NavigationInterface $navigation) {
      foreach ($app->navigationProviders as $provider) {
        if (is_string ($provider))
          $provider = $this->injector->make ($provider);
        $provider->defineNavigation ($navigation);
      }
    });
  }

}
