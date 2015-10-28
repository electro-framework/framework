<?php
namespace Selenia\Tasks;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\ModuleInterface;

class TasksServices implements ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module
      ->registerTasksFromClass ('Selenia\Tasks\CoreTasks')
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
