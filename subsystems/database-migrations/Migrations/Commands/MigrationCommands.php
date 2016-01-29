<?php
namespace Selenia\Migrations\Commands;

use Phinx\Console\Command;
use Phinx\Console\Command\AbstractCommand;
use Robo\Config;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Core\ConsoleApplication\Lib\ModulesUtil;
use Selenia\Core\ConsoleApplication\Services\ConsoleIO;
use Selenia\Migrations\Config\MigrationsSettings;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Database migration commands.
 */
class MigrationCommands
{
  /**
   * @var string Used internally to pass information to config.php
   */
  static $migrationsPath;
  /**
   * @var string Used internally to pass information to config.php
   */
  static $migrationsTable;
  /**
   * @var SymfonyConsole
   */
  private $console;
  /**
   * @var ConsoleIO
   */
  private $io;
  /**
   * @var ModulesUtil
   */
  private $modulesUtil;
  /**
   * @var ModulesRegistry
   */
  private $registry;
  /**
   * @var MigrationsSettings
   */
  private $settings;

  function __construct (MigrationsSettings $settings, ConsoleIO $io, ModulesRegistry $registry,
                        ModulesUtil $modulesUtil, SymfonyConsole $console)
  {
    $this->io          = $io;
    $this->registry    = $registry;
    $this->modulesUtil = $modulesUtil;
    $this->console     = $console;
    $this->settings    = $settings;
  }

  static protected function getConfigPath ()
  {
    return updir (__DIR__, 2) . '/config.php';
  }

  /**
   * Create a new database migration
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param string $name       The name of the migration (a valid PHP class name)
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $class|l Use a class implementing "Phinx\Migration\CreationInterface" to generate the template
   * @option $template|t Use an alternative template
   * @return int Status code
   */
  function migrationCreate ($moduleName = null, $name = null, $options = [
    'class|l'    => null,
    'template|t' => null,
  ])
  {
    $this->setupModule ($moduleName);
    while (!$name) {
      $name = str_camelize ($this->io->ask ("Migration description:"), true);
    }
    $command = new Command\Create;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      'name'            => $name,
      '--class'         => $options['class'],
      '--template'      => $options['template'],
      $moduleName,
    ])->prune ()->A);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Reverts the last migration of a specific module, or optionally up to a specific version
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to rollback to
   * @return int Status code
   */
  function migrationRollback ($moduleName = null, $options = [
    'target|t' => null,
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\Rollback;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--target'        => $options['target'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Run all available migrations of a specific module, optionally up to a specific version
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to migrate to
   * @return int Status code
   */
  function migrationRun ($moduleName = null, $options = [
    'target|t' => null,
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\Migrate;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--target'        => $options['target'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Print a list of all migrations of a specific module, along with their current status
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $format|f      The output format. Allowed values: 'json'. If not specified, text is output.
   * @return int Status code
   */
  function migrationStatus ($moduleName = null, $options = [
    'format|f' => '',
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\Status;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--format'        => $options['format'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Sets the migrations folder path for subsequent commands.
   *
   * @param string $moduleName vendor-name/package-name
   */
  protected function setupMigrationConfig ($moduleName)
  {
    self::$migrationsPath  = $this->registry->getModule ($moduleName)->path . '/' . $this->settings->migrationsPath ();
    self::$migrationsTable = 'migrations_of_' . str_replace ('/', '_', dehyphenate ($moduleName));
  }

  private function runMigrationCommand (AbstractCommand $command, InputInterface $input)
  {
    /** @var OutputInterface $output */
    $output = Config::get ('output');
    $buf    = new BufferedOutput ($output->getVerbosity (), $output->isDecorated (), $output->getFormatter ());
    $r      = $command->run ($input, $buf);
    $result = $this->suppressHeader ($buf->fetch ());
    $output->write ($result);
    return $r;
  }

  /**
   * Prepares the migrations context for running on the specified module.
   *
   * It also validates the module name and/or asks for it, if empty. In the later case, the `$moduleName` argument will
   * be updated on the caller.
   *
   * @param string $moduleName vendor-name/package-name
   */
  private function setupModule (&$moduleName)
  {
    $this->modulesUtil->selectModule ($moduleName, true);
    $this->setupMigrationConfig ($moduleName);
  }

  private function suppressHeader ($text)
  {
    /** @var OutputInterface $output */
    $output = Config::get ('output');
    return $output->getVerbosity () == OutputInterface::VERBOSITY_NORMAL
      ? preg_replace ('/^.*?\n\n/s', PHP_EOL, $text)
      : $text;
  }

}
