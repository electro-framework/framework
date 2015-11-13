<?php
namespace Selenia\Sessions\Config;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Routing\Router;

class RoutingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->delegate ('Selenia\Interfaces\RouterInterface', function () {
        return Router::$current;
      });
  }

}
