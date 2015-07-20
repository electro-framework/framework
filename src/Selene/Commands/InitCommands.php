<?php
namespace Selene\Commands;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Selene\Tasks\ChmodEx;
use Selene\Traits\CommandAPIInterface;

/**
 * Implmenents the Selene task runner's pre-set init:xxx commands.
 */
trait InitCommands
{
  use CommandAPIInterface;

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
    $envPath = "{$this->app()->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $this->error ("The applicatio is already initialized");

    $this->clear ();
    $this->yell ("Selene Configuration Wizard");
    $this->title ("Creating required files and directories...");
    $this->initStorage (['overwrite' => true]);
    $this->initConfig (['overwrite' => true]);
    $this->done ("Initialization completed successfully");
  }

  /**
   * Initializes the application's configuration (.env file)
   *
   * @param array $opts
   * @option $overwrite|o Discards the current .env file if it already exists
   */
  function initConfig ($opts = ['overwrite|o' => false])
  {
    $envPath = "{$this->app()->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $this->error (".env file already exists");
    $this->fs ()->copy ("{$this->app()->scaffoldsPath}/.env", $envPath, true)->run ();

    $this->title ("Configuring the application...");

    $LANG = $this->askDefault ("What is the application's main language? (en | pt | ...)", 'en');
    do {
      $DB_DRIVER = $this->askDefault ("Which database kind are you going to use? (sqlite | mysql)", 'none');
    } while ($DB_DRIVER != 'sqlite' && $DB_DRIVER != 'mysql' && $DB_DRIVER != 'none');

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
        $DB_DATABASE = "../private/storage/database/db.sqlite";
        break;
      case 'mysql':
        $DB_DATABASE = $this->ask ("Database name");
        if (!$DB_DATABASE) $this->comment ("Database name will be determined by MySQL from the username (if so configured)");
        $DB_HOST     = $this->askDefault ("Database host domain", $this->env ('DB_HOST', 'localhost'));
        $DB_USERNAME = $this->askDefault ("Database username", $this->env ('DB_USERNAME'));
        $DB_PASSWORD = $this->askDefault ("Database password", $this->env ('DB_PASSWORD'));
        if ($this->confirm ("Do you which to set advanced database connection options? [n]")) {
          $DB_CHARSET     = $this->askDefault ("Database character set", 'utf8');
          $DB_COLLATION   = $this->askDefault ("Database collation", 'utf8_unicode_ci');
          $DB_PORT        = $this->ask ("Database port [disable]");
          $DB_UNIX_SOCKET = $this->ask ("Database UNIX socket [disable]");
        }
        else {
          $DB_CHARSET   = 'utf8';
          $DB_COLLATION = 'utf8_unicode_ci';
        }
        break;
    }
    $this->nl ();
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
    $target = $this->app()->storagePath;
    if (file_exists ($target)) {
      if (get ($opts, 'overwrite'))
        (new DeleteDir ($target))->run ();
      else $this->error ("Directory already exists");
    }
    (new CopyDir (["{$this->app()->scaffoldsPath}/storage" => $target]))->run ();
    (new ChmodEx ($target))->dirs (0770)->files (0660)->run ();

    $this->say ("Storage directory created");
  }

  private function env ($var, $default = '')
  {
    $v = getenv ($var);

    return $v == '' || $v[0] == '%' ? $default : $v;
  }

}
