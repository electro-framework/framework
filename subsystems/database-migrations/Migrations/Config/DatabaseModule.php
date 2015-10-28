<?php
namespace Selenia\Migrations\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Migrations\Commands\Commands;

class DatabaseModule implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module->registerTasksFromClass (Commands::ref);
  }

}
