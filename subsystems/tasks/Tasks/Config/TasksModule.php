<?php
namespace Electro\Tasks\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Tasks\Tasks\CoreTasks;

class TasksModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector->share (TasksSettings::class);
    });

    $boot->on (Bootstrapper::CONFIGURE, function (ModuleServices $module) {
      $module
        ->registerTasksFromClass (CoreTasks::class);
    });
  }

}
