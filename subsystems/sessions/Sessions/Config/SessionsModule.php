<?php
namespace Selenia\Sessions\Config;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Sessions\Services\Session;

class SessionsModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\SessionInterface', Session::ref)
      ->share (Session::ref);
  }

}
