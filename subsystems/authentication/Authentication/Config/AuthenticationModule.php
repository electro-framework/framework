<?php
namespace Selenia\Authentication\Config;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Interfaces\UserInterface;

class AuthenticationModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (UserInterface::class)
      ->share (AuthenticationSettings::class)
      ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
        return $injector->make ($settings->userModel ());
      });
  }
}
