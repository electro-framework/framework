<?php
namespace Electro\Tasks\Config;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\ConsoleApplication\Config\ConsoleSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Tasks\Tasks\CoreTasks;
use const Electro\Core\Assembly\Services\CONFIGURE;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class TasksModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper
      //
      ->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
        $injector->share (TasksSettings::class);
      })
      //
      ->on (CONFIGURE, function (ConsoleSettings $consoleSettings) {
        $consoleSettings->registerTasksFromClass (CoreTasks::class);
      });
  }

}
