<?php
namespace Selenia\Authentication\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\UserInterface;

class AuthenticationModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (UserInterface::class, 'user')
      ->share (AuthenticationSettings::class)
      ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
        return $injector->make ($settings->userModel ());
      });
  }
}
