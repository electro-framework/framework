<?php
namespace Selenia\Tasks\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Tasks\Tasks\CoreTasks;

class TasksModule implements ModuleInterface
{
  function configure (ModuleServices $module, InjectorInterface $injector)
  {
    $module
      ->registerTasksFromClass (CoreTasks::class);
    $injector->share (TasksSettings::class);
  }
}
