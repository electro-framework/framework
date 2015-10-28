<?php
namespace Selenia\Migrations;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;

class DatabaseMigrationsServices implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module->registerTasksFromClass ('Selenia\Migrations\Commands');
  }

}
