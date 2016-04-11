<?php
namespace Selenia\Core\Assembly\Services;

use PhpKit\Connection;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Core\ConsoleApplication\ConsoleApplication;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Migrations\Commands\MigrationCommands;
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

  /**
   * Performs uninstallation clean up tasks before the module is actually uninstalled.
   *
   * @param string $moduleName
   * @return int 0 for success.
   */
  function cleanUpModule ($moduleName)
  {
    $io = $this->io;
    $io->writeln ("Cleaning up <info>$moduleName</info>");
    $status = 0;
    if ($this->moduleHasMigrations ($moduleName)) {
      $migrations = $this->getMigrationsOf ($moduleName);
      if ($migrations) {
        $io->nl ()->say ("  Updating the database");
        $status = $this->consoleApp->runAndCapture (
          'migrate:reset', [$moduleName], $outStr, $io->getOutput ()
        );
        if (!$status) {
          // Drop migrations table.
          $table = MigrationCommands::$migrationsTable;
          $con   = Connection::getFromEnviroment ();
          if ($con->isAvailable ())
            $con->getPdo ()->query ("DROP TABLE $table");
        }
        else $io->error ("Error while rolling back migrations. Status $status");
        $io->indent (2)->write ($outStr)->indent ();
      }
    }
    return $status;
  }

  /**
   * Runs when module:refresh ends.
   */
  public function end ()
  {
    $this->io->nl ();
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
    $this->consoleApp->runAndCapture ('migration:status', [$moduleName, '--format=json'], $outStr, null, false);
    if (!preg_match ('/\{.*\}$/', $outStr, $m)) return [];
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

    foreach ($modules as $module) {
      $this->io->writeln ("  <info>■</info> $module->name");
      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    if ($this->moduleHasMigrations ($module)) {
      $io         = $this->io;
      $migrations = $this->getMigrationsOf ($module->name);
      foreach ($migrations as $migration) {
        if ($migration->migration_status == 'down') {
          $io->nl ()->say ("    Updating the database");
          $status = $this->consoleApp->runAndCapture (
            'migrate', [$module->name], $outStr, $io->getOutput ()
          );
          if ($status)
            $io->error ("Error while migrating. Status $status");
          $io->indent (4)->write ($outStr)->indent ()->nl ();
          break;
        }
      }
    }
  }

}
