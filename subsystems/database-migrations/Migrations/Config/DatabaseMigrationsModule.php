<?php
namespace Selenia\Migrations\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Migrations\Commands\MigrationCommands;

class DatabaseMigrationsModule implements ModuleInterface
{
  function configure (ModuleServices $module, InjectorInterface $injector)
  {
    $module->registerTasksFromClass (MigrationCommands::class);
    $injector->share (MigrationsSettings::class);
  }

}
