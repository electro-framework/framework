<?php
namespace Selenia\Tasks;
use Robo\Task\FileSystem\FilesystemStack;
use Selenia\Tasks\Commands\BuildCommands;
use Selenia\Tasks\Commands\InitCommands;
use Selenia\Tasks\Commands\ModuleCommands;
use Selenia\Console\TaskRunner\ConsoleIO;

/**
 * The preset Selenia console tasks configuration for Selenia's task runner.
 */
class CoreTasks
{
  use InitCommands;
  use BuildCommands;
  use ModuleCommands;

  /** @var ConsoleIO */
  private $io;

  function __construct (ConsoleIO $io)
  {
    $this->io = $io;
  }

  protected function app ()
  {
    global $application;
    return $application;
  }

  protected function io ()
  {
    return $this->io;
  }

  protected function fs ()
  {
    return new FilesystemStack;
  }

  protected function moduleConfig ($key)
  {
    return get ($this->app ()->config['core-tasks'], $key);
  }

}
