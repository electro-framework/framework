<?php
namespace Electro\Authentication\Config;

use Electro\Kernel\Lib\ModuleInfo;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Services\Loader;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class AuthenticationModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->share (UserInterface::class, 'user')
        ->share (AuthenticationSettings::class)
        ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
          return $injector->make ($settings->userModel ());
        });
    });
  }
}
