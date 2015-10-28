<?php
namespace Selenia\Migrations;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;

class DatabaseModule implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module->registerTasksFromClass (Commands::ref);
  }

}
