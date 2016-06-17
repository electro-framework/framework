<?php
namespace Electro\Tasks\Tasks;

use Robo\Task\FileSystem\FilesystemStack;
use Electro\Application;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Core\ConsoleApplication\ConsoleApplication;
use Electro\Core\ConsoleApplication\Lib\ModulesUtil;
use Electro\Core\ConsoleApplication\Services\ConsoleIO;
use Electro\Tasks\Commands\InitCommands;
use Electro\Tasks\Commands\ModuleCommands;
use Electro\Tasks\Config\TasksSettings;

/**
 * The preset Electro console tasks configuration for Electro's task runner.
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
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var FilesystemStack
   */
  private $fs;
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
                        TasksSettings $settings, ConsoleApplication $consoleApp)
  {
    $this->io              = $io;
    $this->modulesUtil     = $modulesUtil;
    $this->app             = $app;
    $this->modulesRegistry = $modulesRegistry;
    $this->settings        = $settings;
    $this->consoleApp      = $consoleApp;
    $this->fs              = new FilesystemStack;
  }

}
