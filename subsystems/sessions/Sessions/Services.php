<?php
namespace Selenia\Sessions;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class Services implements ServiceProviderInterface
{

  function register (InjectorInterface $injector)
  {
    $injector->delegate ('Selenia\Interfaces\SessionInterface', function () {
      global $session;
      return $session = $session ?: new Session;
    });
  }

}
