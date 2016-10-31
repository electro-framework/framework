<?php
namespace Electro\Authentication\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\UserInterface;

class AuthenticationModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector) {
      $injector
        ->share (UserInterface::class, 'user')
        ->share (AuthenticationSettings::class)
        ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
          return $injector->make ($settings->userModel ());
        });
    });
  }
}
