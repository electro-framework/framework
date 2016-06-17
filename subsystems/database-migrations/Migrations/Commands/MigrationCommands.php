<?php
namespace Electro\Migrations\Commands;

use Phinx\Console\Command;
use Phinx\Console\Command\AbstractCommand;
use Robo\Config;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Core\ConsoleApplication\Lib\ModulesUtil;
use Electro\Core\ConsoleApplication\Services\ConsoleIO;
use Electro\Migrations\Config\MigrationsSettings;
use Electro\Plugins\IlluminateDatabase\DatabaseAPI;
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
   * @var string Used internally to pass information to config.php
   */
  static $seedsPath;
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
   * Note: if the Illuminate Database plugin is installed, the generated migration will use its schema builder instead
   * of the one from Phinx.
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param string $name       [optional] The name of the migration (a human-friendly description, it may contain
   *                           spaces, but not accented characters). If not specified, the user will be prompted for it
   * @param array  $options
   * @option $class|l Use a class implementing "Phinx\Migration\CreationInterface" to generate the template
   * @option $template|t Use an alternative template
   * @option $no-doc|d Do not generate a documentation block.
   * @return int Status code
   */
  function makeMigration ($moduleName = null, $name = null, $options = [
    'class|l'    => null,
    'template|t' => null,
    'no-doc|d'   => false,
  ])
  {
    $this->setupModule ($moduleName);
    while (!$name) {
      $name = str_camelize ($this->io->ask ("Migration description:"), true);
    }
    $template = $options['template'];
    $class    = $options['class'];
    if (!$template && !$class) {
      if (class_exists (DatabaseAPI::class))
        $template = sprintf ('%s/templates/IlluminateMigration%s.php.template', updir (__DIR__, 2),
          $options['no-doc'] ? '-nodoc' : '');
    }
    $command = new Command\Create;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      'name'            => $name,
      '--class'         => $class,
      '--template'      => $template,
      $moduleName,
    ])->prune ()->A);
    $input->setInteractive (false);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Create a new database seeder
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param string $name       [optional] The name of the seeder (a human-friendly description, it may contain
   *                           spaces, but not accented characters). If not specified, the user will be prompted for it
   * @return int Status code
   */
  function makeSeeder ($moduleName = null, $name = null)
  {
    $this->setupModule ($moduleName);
    while (!$name) {
      $name = str_camelize ($this->io->ask ("Seeder name:"), true);
    }
    $command = new Command\SeedCreate;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      'name'            => $name,
      $moduleName,
    ])->prune ()->A);
    $input->setInteractive (false);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Run all available migrations of a specific module, optionally up to a specific version
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to migrate to
   * @return int Status code
   */
  function migrate ($moduleName = null, $options = [
    'target|t' => null,
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\Migrate;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--target'        => get ($options, 'target'),
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    $status = $this->runMigrationCommand ($command, $input);
    //TODO: temporary workaround for bug
    $status = $this->runMigrationCommand ($command, $input);
    return $status;
  }

  /**
   * Reset and re-run all migrations
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $seed|The version number to migrate to
   * @return int Status code
   */
  function migrateRefresh ($moduleName = null, $options = [
    '--seed' => null,
  ])
  {
    $this->setupModule ($moduleName);
    $r = $this->migrateRollback ($moduleName, ['target' => 0]);
    if ($r) return $r;
    $r = $this->migrate ($moduleName);
    if ($r) return $r;
    if ($options['seed'])
      ; //TODO run seeders
    return 0;
  }

  /**
   * Rollback all database migrations
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @return int Status code
   */
  function migrateReset ($moduleName = null)
  {
    $this->setupModule ($moduleName);
    return $this->migrateRollback ($moduleName, ['target' => 0]);
  }

  /**
   * Reverts the last migration of a specific module, or optionally up to a specific version
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to rollback to
   * @option $date|d   The date to rollback to
   * @return int Status code
   */
  function migrateRollback ($moduleName = null, $options = [
    'target|t' => null,
    'date|d'   => null,
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\Rollback;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--target'        => get ($options, 'target'),
      '--date'          => get ($options, 'date'),
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    $status = $this->runMigrationCommand ($command, $input);
    //TODO: temporary workaround for bug
    $status = $this->runMigrationCommand ($command, $input);
    return $status;
  }

  /**
   * Run all available seeders of a specific module, or just a specific seeder
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $seeder|s The name of the seeder (in camel case)
   * @return int Status code
   */
  function migrateSeed ($moduleName = null, $options = [
    'seeder|s' => null,
  ])
  {
    $this->setupModule ($moduleName);
    $command = new Command\SeedRun;
    $command->setApplication ($this->console);
    $input = new ArrayInput(PA ([
      '--configuration' => self::getConfigPath (),
      '--environment'   => 'main',
      '--seed'          => get ($options, 'seeder'),
      $moduleName,
    ])->prune ()->A);
    return $this->runMigrationCommand ($command, $input);
  }

  /**
   * Print a list of all migrations of a specific module, along with their current status
   *
   * @param string $moduleName [optional] The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $format|f      The output format. Allowed values: 'json'. If not specified, text is output.
   * @return int Status code
   */
  function migrateStatus ($moduleName = null, $options = [
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
    $module                = $this->registry->getModule ($moduleName);
    $short                 = $module->getShortName ();
    $uid                   = substr (md5 ($module->name), 0, 4);
    self::$migrationsTable = sprintf ('_%s_migrations_%s', strtolower (dehyphenate ($short)), $uid);
    self::$migrationsPath  = $module->path . '/' . $this->settings->migrationsPath ();
    self::$seedsPath       = $module->path . '/' . $this->settings->seedsPath ();
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
