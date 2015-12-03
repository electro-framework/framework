<?php
namespace Selenia\Routing\Config;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Routing\Navigation\Navigation;
use Selenia\Routing\Navigation\NavigationLink;

class NavigationModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (NavigationInterface::class, Navigation::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
