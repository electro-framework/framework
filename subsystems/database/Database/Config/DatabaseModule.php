<?php
namespace Electro\Database\Config;

use PhpKit\Connection;
use PhpKit\ConnectionInterface;
use Electro\Database\Lib\DebugConnection;
use Electro\Database\Services\ModelController;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModelControllerInterface;

class DatabaseModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ConnectionInterface::class)
      ->delegate (ConnectionInterface::class, function ($debugConsole) {
        $con = $debugConsole ? new DebugConnection : new Connection;
        return $con->getFromEnviroment ();
      })
      ->alias (ModelControllerInterface::class, ModelController::class)
      ->share (ModelController::class);
  }

}
