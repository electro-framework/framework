<?php
namespace Electro\Database\Config;

use Electro\Database\Lib\DebugConnection;
use Electro\Database\Services\ModelController;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use PhpKit\ExtPDO\Connection;
use PhpKit\ExtPDO\Connections;
use PhpKit\ExtPDO\Interfaces\ConnectionInterface;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;

class DatabaseModule implements ModuleInterface
{
  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector
          ->delegate (ConnectionInterface::class, function ($debugConsole) {
            return $debugConsole ? new DebugConnection : new Connection;
          })
          ->share (ConnectionsInterface::class)
          ->delegate (ConnectionsInterface::class, function ($debugConsole) {
            $connections = new Connections;
            $connections->setConnectionClass ($debugConsole ? DebugConnection::class : Connection::class);
            return $connections;
          })
          ->alias (ModelControllerInterface::class, ModelController::class)
          ->share (ModelController::class);
      });
  }

}
