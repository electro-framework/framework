<?php
namespace Electro\Migrations\Config;

use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Migrations\Commands\MigrationCommands;

class DatabaseMigrationsModule implements ModuleInterface
{
  function configure (ModuleServices $module, InjectorInterface $injector)
  {
    $module->registerTasksFromClass (MigrationCommands::class);
    $injector->share (MigrationsSettings::class);
  }

}
