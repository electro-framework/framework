<?php
namespace Selenia\Tasks\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Tasks\Tasks\CoreTasks;

class TasksModule implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module
      ->registerTasksFromClass (CoreTasks::ref)
      ->setDefaultConfig ([
        'core-tasks' => [
          /**
           * The path of the Core Tasks's scaffolds's directory, relative to the project's directory.
           * @var string
           */
          'scaffoldsPath' => dirname (__DIR__) . '/scaffolds',
        ],
      ]);
  }
}
