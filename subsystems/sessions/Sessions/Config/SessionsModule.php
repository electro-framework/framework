<?php
namespace Electro\Sessions\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Bootstrapper;
use Electro\Sessions\Services\Session;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class SessionsModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->alias (SessionInterface::class, Session::class)
        ->share (Session::class, 'session');
    });
  }

}
