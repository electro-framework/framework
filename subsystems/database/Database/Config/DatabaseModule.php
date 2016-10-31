<?php
namespace Electro\Database\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Database\Lib\DebugConnection;
use Electro\Database\Services\ModelController;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\ModuleInterface;
use PhpKit\Connection;
use PhpKit\ConnectionInterface;

class DatabaseModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector) {
      $injector
        ->share (ConnectionInterface::class)
        ->delegate (ConnectionInterface::class, function ($debugConsole) {
          $con = $debugConsole ? new DebugConnection : new Connection;
          return $con->getFromEnviroment ();
        })
        ->alias (ModelControllerInterface::class, ModelController::class)
        ->share (ModelController::class);
    });
  }

}
