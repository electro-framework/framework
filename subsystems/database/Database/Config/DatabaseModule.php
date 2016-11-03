<?php
namespace Electro\Database\Config;

use Electro\Database\Lib\DebugConnection;
use Electro\Database\Services\ModelController;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Loader;
use PhpKit\Connection;
use PhpKit\ConnectionInterface;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class DatabaseModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
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
