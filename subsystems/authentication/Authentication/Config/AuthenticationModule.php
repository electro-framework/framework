<?php
namespace Electro\Authentication\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ApiProfile;
use Electro\Profiles\WebProfile;

class AuthenticationModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ApiProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector
          ->share (UserInterface::class, 'user')
          ->share (AuthenticationSettings::class)
          ->delegate (UserInterface::class, function (AuthenticationSettings $settings) use ($injector) {
            return $injector->make ($settings->userModel ());
          });
      });
  }
}
