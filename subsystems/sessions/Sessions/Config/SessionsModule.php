<?php
namespace Electro\Sessions\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Sessions\Services\Session;

class SessionsModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector) {
      $injector
        ->alias (SessionInterface::class, Session::class)
        ->share (Session::class, 'session');
    });
  }

}
