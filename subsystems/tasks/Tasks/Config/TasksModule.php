<?php
namespace Electro\Tasks\Config;

use Electro\ConsoleApplication\Config\ConsoleSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Loader;
use Electro\Tasks\Tasks\CoreTasks;
use const Electro\Kernel\Services\CONFIGURE;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class TasksModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader
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
