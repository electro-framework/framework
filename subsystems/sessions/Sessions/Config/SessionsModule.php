<?php
namespace Selenia\Sessions\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Sessions\Services\Session;

class SessionsModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (SessionInterface::class, Session::class)
      ->share (Session::class, 'session');
  }

}
