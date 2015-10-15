<?php

ModuleOptions (__DIR__, [
  'tasks'  => 'Selenia\CoreTasks',
  'config' => [
    'core-tasks' => [
      /**
       * The path of the Core Tasks's scaffolds's directory, relative to the project's directory.
       * @var string
       */
      'scaffoldsPath' => 'private/plugins/selenia-components/core-tasks/scaffolds',
    ],
  ],
]);
