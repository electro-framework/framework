<?php
namespace Selenia\Core\Assembly\Services;

use PhpKit\Connection;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Core\ConsoleApplication\ConsoleApplication;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Migrations\Config\MigrationsSettings;

/**
 * Manages modules installation, update and removal.
 *
 * > <p>**Warning:** no validation of module names is performed on methods of this class. It is assumed this service is
 * only invoked for valid modules. Validation should be performed on the caller.
 */
class ModulesInstaller
{
  /**
   * @var MigrationsSettings
   */
  public $migrationsSettings;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var ConsoleIOInterface
   */
  private $io;
  /**
   * @var ModulesRegistry
   */
  private $registry;

  function __construct (Application $app, ConsoleApplication $consoleApp)
  {
    $this->app        = $app;
    $this->consoleApp = $consoleApp;
    $this->io         = $consoleApp->getIO ();
  }

  function begin ()
  {
    $this->io->banner ('Selenia Modules Installer');
  }

  /**
   * Performs uninstallation clean up tasks before the module is actually uninstalled.
   *
   * @param string $moduleName
   * @return int 0 for success.
   */
  function cleanUpModule ($moduleName)
  {
    $this->io->writeln ("Cleaning up <info>$moduleName</info>");
    $status = 0;
    if ($this->moduleHasMigrations ($moduleName)) {
      $migrations = $this->getMigrationsOf ($moduleName);
      if ($migrations) {
        $this->io->nl ()->say ("  Updating the database");
        $status = $this->consoleApp->runAndCapture ('migration:rollback', ['-t', '0', $moduleName],
          $out, true, $this->io->getOutput ()->getVerbosity ());
        $this->io->indent (2)->write ($out)->indent ();
      }
    }
    return $status;
  }

  function end ()
  {
    $this->io
      ->writeln ('<info>Modules configuration completed</info>')
      ->nl ();
  }

  function setRegistry (ModulesRegistry $registry)
  {
    $this->registry = $registry;
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function setupNewModules (array $modules)
  {
    if (!$modules) return;
    $this->io->title ('Configuring New Modules');
    $this->setupModules ($modules);
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function updateModules (array $modules)
  {
    if (!$modules) return;
    $this->io->title ("Re-check Installed Modules");
    $this->setupModules ($modules);
  }

  /**
   * @param string $moduleName
   * @return \stdClass[]
   */
  private function getMigrationsOf ($moduleName)
  {
    $this->consoleApp->runAndCapture ('migration:status', [$moduleName, '--format=json'], $out, false);
    if (!preg_match ('/\{.*\}$/', $out, $m)) return [];
    return json_decode ($m[0])->migrations;
  }

  /**
   * @param string|ModuleInfo $module
   * @return bool
   */
  private function moduleHasMigrations ($module)
  {
    if (is_string ($module))
      $module = $this->registry->getModule ($module);
    $path = "$module->path/" . $this->migrationsSettings->migrationsPath ();
    return fileExists ($path);
  }

  private function setupModules (array $modules)
  {
    $databaseIsAvailable = Connection::getFromEnviroment ()->isAvailable ();
    $runMigrations       = $databaseIsAvailable && $this->migrationsSettings;

//    var_dump($modules);exit;
    foreach ($modules as $module) {
      $this->io->writeln ("  <info>■</info> $module->name");
      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
    $this->io->nl ();
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    if ($this->moduleHasMigrations ($module)) {
      $migrations = $this->getMigrationsOf ($module->name);
      foreach ($migrations as $migration) {
        if ($migration->migration_status == 'down') {
          $this->io->nl ()->say ("    Updating the database");
          $this->consoleApp->runAndCapture ('migration:run', [$module->name], $out, true,
            $this->io->getOutput ()->getVerbosity ());
          $this->io->indent (4)->write ($out)->indent ()->nl ();
          break;
        }
      }
    }
  }

}
