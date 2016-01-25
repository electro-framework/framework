<?php
namespace Selenia\Tasks\Tasks;

use Robo\Task\FileSystem\FilesystemStack;
use Selenia\Application;
use Selenia\Console\Lib\ModulesUtil;
use Selenia\Console\Services\ConsoleIO;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Tasks\Commands\InitCommands;
use Selenia\Tasks\Commands\ModuleCommands;
use Selenia\Tasks\Config\TasksSettings;

/**
 * The preset Selenia console tasks configuration for Selenia's task runner.
 */
class CoreTasks
{
  use InitCommands;
//  use BuildCommands;
  use ModuleCommands;

  /**
   * @var Application
   */
  private $app;
  /**
   * @var ConsoleIO
   */
  private $io;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;
  /**
   * @var ModulesUtil
   */
  private $modulesUtil;
  /**
   * @var TasksSettings
   */
  private $settings;

  function __construct (ConsoleIO $io, Application $app, ModulesUtil $modulesUtil, ModulesRegistry $modulesRegistry,
                        TasksSettings $settings)
  {
    $this->io              = $io;
    $this->modulesUtil     = $modulesUtil;
    $this->app             = $app;
    $this->modulesRegistry = $modulesRegistry;
    $this->settings        = $settings;
  }

  protected function app ()
  {
    return $this->app;
  }

  protected function fs ()
  {
    return new FilesystemStack;
  }

  protected function io ()
  {
    return $this->io;
  }

  protected function modulesRegistry ()
  {
    return $this->modulesRegistry;
  }

  protected function modulesUtil ()
  {
    return $this->modulesUtil;
  }

}
