<?php
namespace Selenia\Navigation\Config;

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
    $injector
      ->share (NavigationInterface::class)
      ->alias (NavigationInterface::class, Navigation::class)
      ->alias (NavigationLinkInterface::class, NavigationLink::class);
  }

}
