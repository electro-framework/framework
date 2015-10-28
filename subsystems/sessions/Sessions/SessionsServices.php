<?php
namespace Selenia\Sessions;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class SessionsServices implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\SessionInterface', 'Selenia\Sessions\Session')
      ->share ('Selenia\Sessions\Session');
  }

}
