<?php
namespace Electro\Database\Config;

use Electro\Database\Lib\DebugConnection;
use Electro\Database\Services\ModelController;
use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use PhpKit\ExtPDO\Connection;
use PhpKit\ExtPDO\Connections;
use PhpKit\ExtPDO\Interfaces\ConnectionInterface;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;

class DatabaseModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ConsoleProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector
          ->delegate (ConnectionInterface::class, function (DebugSettings $debugSettings) {
            return $debugSettings->isWebConsoleAvailable () && $debugSettings->logDatabase ? new DebugConnection : new Connection;
          })
          ->share (ConnectionsInterface::class)
          ->delegate (ConnectionsInterface::class, function (DebugSettings $debugSettings) {
            $connections = new Connections;
            $connections->setConnectionClass ($debugSettings->isWebConsoleAvailable () && $debugSettings->logDatabase
              ? DebugConnection::class : Connection::class);
            return $connections;
          })
          ->alias (ModelControllerInterface::class, ModelController::class)
          ->share (ModelController::class);
      });
  }

}
