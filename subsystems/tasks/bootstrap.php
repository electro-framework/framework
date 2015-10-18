<?php

ModuleOptions (__DIR__, [
  'tasks'  => 'Selenia\Tasks\CoreTasks',
  'config' => [
    'core-tasks' => [
      /**
       * The path of the Core Tasks's scaffolds's directory, relative to the project's directory.
       * @var string
       */
      'scaffoldsPath' => __DIR__ . '/scaffolds',
    ],
  ],
]);
