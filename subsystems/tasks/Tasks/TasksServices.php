<?php
namespace Selenia\Tasks;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class TasksServices implements ServiceProviderInterface
{
  function boot () { }

  function register (InjectorInterface $injector)
  {
    ModuleOptions (dirname (__DIR__), [
      'tasks'  => 'Selenia\Tasks\CoreTasks',
      'config' => [
        'core-tasks' => [
          /**
           * The path of the Core Tasks's scaffolds's directory, relative to the project's directory.
           * @var string
           */
          'scaffoldsPath' => dirname(__DIR__) . '/scaffolds',
        ],
      ],
    ]);
  }

}
