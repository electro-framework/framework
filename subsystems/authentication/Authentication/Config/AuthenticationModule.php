<?php
namespace Electro\Authentication\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\UserInterface;

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
