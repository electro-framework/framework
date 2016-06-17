<?php
namespace Electro\Sessions\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Sessions\Services\Session;

class SessionsModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (SessionInterface::class, Session::class)
      ->share (Session::class, 'session');
  }

}
