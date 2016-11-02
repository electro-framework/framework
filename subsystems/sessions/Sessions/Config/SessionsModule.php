<?php
namespace Electro\Sessions\Config;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Sessions\Services\Session;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

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
