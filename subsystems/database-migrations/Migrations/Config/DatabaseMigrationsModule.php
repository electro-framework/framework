<?php
namespace Selenia\Migrations\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Migrations\Commands\Commands;

class DatabaseMigrationsModule implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module->registerTasksFromClass (Commands::class);
  }

}
