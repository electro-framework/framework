<?php

namespace Electro\Tasks\Tasks;

use Electro\Caching\Config\CachingSettings;
use Electro\ConsoleApplication\ConsoleApplication;
use Electro\ConsoleApplication\Lib\ModulesUtil;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesInstaller;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Tasks\Commands\InitCommands;
use Electro\Tasks\Commands\MiscCommands;
use Electro\Tasks\Commands\ModuleCommands;
use Electro\Tasks\Commands\ServerCommands;
use Electro\Tasks\Config\TasksSettings;
use PhpKit\Flow\FilesystemFlow;
use Robo\Task\Composer\Update;
use Robo\Task\FileSystem\CleanDir;
use Robo\Task\FileSystem\FilesystemStack;

/**
 * The preset Electro console tasks configuration for Electro's task runner.
 */
class CoreTasks
{
  use InitCommands;
//  use BuildCommands;
  use ModuleCommands;
  use ServerCommands;
  use MiscCommands;

  /**
   * @var bool Display the output of Composer commands?
   */
  static $SHOW_COMPOSER_OUTPUT = true;
  /**
   * @var CachingSettings
   */
  private $cachingSettings;
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
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var ModulesInstaller
   */
  private $modulesInstaller;
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

  function __construct (ConsoleIO $io, KernelSettings $kernelSettings, ModulesUtil $modulesUtil,
                        ModulesRegistry $registry, ModulesInstaller $installer, TasksSettings $settings,
                        ConsoleApplication $consoleApp, CachingSettings $cachingSettings)
  {
    $this->io               = $io;
    $this->modulesUtil      = $modulesUtil;
    $this->kernelSettings   = $kernelSettings;
    $this->modulesRegistry  = $registry;
    $this->modulesInstaller = $installer;
    $this->settings         = $settings;
    $this->consoleApp       = $consoleApp;
    $this->fs               = new FilesystemStack;
    $this->cachingSettings  = $cachingSettings;
  }

  /**
   * Clears the content of a directory.
   *
   * @param string $path An absolute or relative filesystem path.
   */
  private function clearDir ($path)
  {
    (new CleanDir($this->app->toAbsolutePath ($path)))->run ();
  }

  /**
   * Runs the `composer update` command.
   */
  private function composerUpdate ()
  {
    (new Update)->printed (self::$SHOW_COMPOSER_OUTPUT)->run ();
  }

  /**
   * Check if a directory is empty.
   *
   * @param string $path
   * @return bool
   */
  private function isDirectoryEmpty ($path)
  {
    return !count (FilesystemFlow::from ($path)->all ());
  }

}
