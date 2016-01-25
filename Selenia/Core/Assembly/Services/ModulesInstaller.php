<?php
namespace Selenia\Core\Assembly\Services;

use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Database\Connection;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Migrations\Config\MigrationsSettings;

/**
 * Manages modules installation, update and removal.
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
   * @var ConsoleIOInterface
   */
  private $io;

  public function __construct (Application $app)
  {
    $this->app = $app;
    if ($app->isConsoleBased)
      $this->io = $app->injector->make (ConsoleIOInterface::class);
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function cleanupRemovedModules (array $modules)
  {
    //nothing yet
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function setupNewModules (array $modules)
  {
    //nothing yet
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function updateModules (array $modules)
  {
    $databaseIsAvailable = Connection::getFromEnviroment()->isAvailable();
    $runMigrations = $databaseIsAvailable && $this->migrationsSettings;

    foreach ($modules as $module) {
      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    $path = "$module->path/" . $this->migrationsSettings->migrationsPath ();
    if (fileExists ($path)) {
      if ($this->io)
        $this->io->nl ()->say ("Running migrations of module <info>$module->name</info>");
      $this->app->runCommand ('migration:run', [$module->name, '-n']);
    }
  }

}
