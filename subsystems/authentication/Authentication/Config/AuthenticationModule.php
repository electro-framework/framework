<?php
namespace Electro\Authentication\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Loader;

class AuthenticationModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader->onRegisterServices (function (InjectorInterface $injector) {
      $injector
        ->share (UserInterface::class, 'user')
        ->share (AuthenticationSettings::class)
        ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
          return $injector->make ($settings->userModel ());
        });
    });
  }
}
