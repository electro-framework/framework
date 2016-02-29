<?php
namespace Selenia\Database\Config;

use PhpKit\Connection;
use PhpKit\ConnectionInterface;
use Selenia\Database\Lib\DebugConnection;
use Selenia\Database\Services\ModelManager;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModelManagerInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class DatabaseModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ConnectionInterface::class)
      ->delegate (ConnectionInterface::class, function ($debugMode) {
        $con = $debugMode ? new DebugConnection : new Connection;
        return $con->getFromEnviroment ();
      })
      ->alias (ModelManagerInterface::class, ModelManager::class);
  }

}
