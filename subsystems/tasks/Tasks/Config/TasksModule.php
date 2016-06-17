<?php
namespace Electro\Tasks\Config;

use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Tasks\Tasks\CoreTasks;

class TasksModule implements ModuleInterface
{
  function configure (ModuleServices $module, InjectorInterface $injector)
  {
    $module
      ->registerTasksFromClass (CoreTasks::class);
    $injector->share (TasksSettings::class);
  }
}
