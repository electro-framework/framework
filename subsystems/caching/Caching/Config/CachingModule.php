<?php
namespace Electro\Caching\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;

class CachingModule implements ModuleInterface
{
  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {

      });
  }

}
