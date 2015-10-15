<?php
namespace Selenia\Commands;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Selenia\Contracts\ApplicationServiceTrait;
use Selenia\Contracts\ConsoleIOServiceTrait;
use Selenia\Contracts\FileSystemStackServiceTrait;
use Selenia\Contracts\ModuleConfigServiceTrait;
use Selenia\TaskRunner;
use Selenia\Tasks\ChmodEx;

/**
 * Implements the Selenia Task Runner's pre-set init:xxx commands.
 */
trait InitCommands
{
  use ConsoleIOServiceTrait;
  use ApplicationServiceTrait;
  use ModuleConfigServiceTrait;
  use FileSystemStackServiceTrait;

  /**
   * (private)
   * This is called by Composer on the post-install event.
   */
  static function runInit ()
  {
    $tmp = dirname (dirname (dirname (dirname (dirname (__DIR__)))));
    require "$tmp/packages/autoload.php";
    (new TaskRunner ())->run (['', 'init']);
  }

  /**
   * Initializes the application after installation, or reinitializes it afterwards
   *
   * Note: this is automatically called after `composer install` runs.
   *
   * @param array $opts
   * @option $overwrite|o Discards the current configuration if it already exists
   */
  function init ($opts = ['overwrite|o' => false])
  {
    $io      = $this->io ();
    $envPath = "{$this->app()->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $io->error ("The application is already initialized");

    $io->clear ()
       ->banner ("Selenia Configuration Wizard")
       ->title ("Creating required files and directories...");
    $this->initStorage (['overwrite' => true]);
    $this->initConfig (['overwrite' => true]);
    $io->done ("Initialization completed successfully");
  }

  /**
   * Initializes the application's configuration (.env file)
   *
   * @param array $opts
   * @option $overwrite|o Discards the current .env file if it already exists
   */
  function initConfig ($opts = ['overwrite|o' => false])
  {
    $io      = $this->io ();
    $envPath = "{$this->app()->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $io->error (".env file already exists");
    $this->fs ()->copy ("{$this->moduleConfig('scaffoldsPath')}/.env", $envPath, true)->run ();

    $io->title ("Configuring the application...");

    $LANG = $io->askDefault ("What is the application's main language? (en | pt | ...)", 'en');
    do {
      $DB_DRIVER = $io->askDefault ("Which database kind are you going to use? (none | sqlite | mysql | pgsql)", 'none');
    } while ($DB_DRIVER != 'sqlite' && $DB_DRIVER != 'mysql' && $DB_DRIVER != 'pgsql' && $DB_DRIVER != 'none');

    $DB_DATABASE    = '';
    $DB_HOST        = '';
    $DB_USERNAME    = '';
    $DB_PASSWORD    = '';
    $DB_CHARSET     = '';
    $DB_COLLATION   = '';
    $DB_PORT        = '';
    $DB_UNIX_SOCKET = '';

    switch ($DB_DRIVER) {
      case 'sqlite':
        $DB_DATABASE = "private/storage/database/db.sqlite";
        break;
      case 'mysql':
        $DB_DATABASE = $io->ask ("Database name");
        if (!$DB_DATABASE) $io
          ->comment ("Database name will be determined by MySQL from the username (if so configured)");
        $DB_HOST     = $io->askDefault ("Database host domain", env ('DB_HOST', 'localhost'));
        $DB_USERNAME = $io->askDefault ("Database username", env ('DB_USERNAME'));
        $DB_PASSWORD = $io->askDefault ("Database password", env ('DB_PASSWORD'));
        if ($io->confirm ("Do you whish to set advanced database connection options? [n]")) {
          $DB_CHARSET     = $io->askDefault ("Database character set", 'utf8');
          $DB_COLLATION   = $io->askDefault ("Database collation", 'utf8_unicode_ci');
          $DB_PORT        = $io->ask ("Database port [disable]");
          $DB_UNIX_SOCKET = $io->ask ("Database UNIX socket [disable]");
        }
        else {
          $DB_CHARSET   = 'utf8';
          $DB_COLLATION = 'utf8_unicode_ci';
        }
        break;
      case 'pgsql':
        do {
          $DB_DATABASE = $io->ask ("Database name");
          if (!$DB_DATABASE) $io->say("You must specify a name");
        } while (!$DB_DATABASE);
        $DB_HOST     = $io->askDefault ("Database host domain", env ('DB_HOST', 'localhost'));
        $DB_USERNAME = $io->askDefault ("Database username", env ('DB_USERNAME'));
        $DB_PASSWORD = $io->askDefault ("Database password", env ('DB_PASSWORD'));
        break;
    }
    $io->nl ();
    (new Replace ($envPath))
      ->from ([
        '%LANG',
        '%DB_DRIVER',
        '%DB_DATABASE',
        '%DB_HOST',
        '%DB_USERNAME',
        '%DB_PASSWORD',
        '%DB_CHARSET',
        '%DB_COLLATION',
        '%DB_PORT',
        '%DB_UNIX_SOCKET',
      ])
      ->to ([
        $LANG,
        $DB_DRIVER,
        $DB_DATABASE,
        $DB_HOST,
        $DB_USERNAME,
        $DB_PASSWORD,
        $DB_CHARSET,
        $DB_COLLATION,
        $DB_PORT,
        $DB_UNIX_SOCKET,
      ])
      ->run ();
  }

  /**
   * Scaffolds the storage directory's structure
   *
   * This is automatically run after composer-create-project.
   * If you cloned the starter project by other means, run this command manually.
   *
   * @param array $opts
   * @option $overwrite|o Discards the target directory if it already exists
   */
  function initStorage ($opts = ['overwrite|o' => false])
  {
    $target = $this->app ()->storagePath;
    if (file_exists ($target)) {
      if (get ($opts, 'overwrite'))
        (new DeleteDir ($target))->run ();
      else $this->io ()->error ("Directory already exists. Use the -o|--overwrite option to re-create the storage directory.");
    }
    (new CopyDir (["{$this->moduleConfig('scaffoldsPath')}/storage" => $target]))->run ();
    (new ChmodEx ($target))->dirs (0770)->files (0660)->run ();

    $this->io ()->say ("Storage directory created");
  }

}
