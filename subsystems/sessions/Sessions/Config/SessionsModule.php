<?php
namespace Selenia\Sessions\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Sessions\Services\Session;

class SessionsModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\SessionInterface', Session::class)
      ->share (Session::class);
  }

}
