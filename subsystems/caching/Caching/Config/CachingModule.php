<?php
namespace Electro\Caching\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ApiProfile;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use Psr\Log\LoggerInterface;

class CachingModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ConsoleProfile::class, ApiProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
//        $injector->alias(CacheLoggerInterface::class, LoggerInterface::class);
      });
  }

}
