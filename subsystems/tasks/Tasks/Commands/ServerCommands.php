<?php
namespace Electro\Tasks\Commands;

use Electro\Application;
use Electro\Interfaces\ConsoleIOInterface;

/**
 * Implements the Electro's development web server commands.
 *
 * @property Application        $app
 * @property ConsoleIOInterface $io
 */
trait ServerCommands
{
  private $PID_FILE = 'private/storage/server.pid';

  /**
   * Starts the development web server
   *
   * @param array $options
   * @option $port|p The TCP/IP port the web server will listen on
   * @option $address|a The IP address the web server will listen on
   * @option $log|l The path of a log file where the server's logging output will be saved
   * @option $global|g If specified, the server will serve all sites under the root web directory
   * @option $root|r The root web directory name (not the path). The directory will be searched for starting at the
   *         current directory and going upwards
   */
  function serverStart ($options = [
    'port|p' => 8000, 'address|a' => 'localhost', 'log|l' => '/dev/null', 'global|g' => false, 'root|r' => 'Sites',
  ])
  {
    $pid = $this->getPID ();
    if ($pid && isRunning ($pid))
      $this->io->error ("The server is already running");
    $dir = getcwd ();
    if ($options['global']) {
      while ($dir) {
        $current = basename ($dir);
        if ($current == $options['root']) break;
        $dir = updir ($dir);
      }
      $root = " -t $dir";
    }
    else $root = '';
    $log   = $options['log'] == '/dev/null' ? '<comment>disabled</comment>' : "<info>{$options['log']}</info>";
    $where = $options['address'] . ':' . $options['port'];
    $pid   = runBackgroundCommand ("php -S $where$root {$this->app->routerFile}", $options['log']);
    file_put_contents ($this->PID_FILE, $pid);
    $this->io->done (sprintf ("The server is now <info>running</info>

Listening at: <info>$where</info>
Publishing:   <info>$dir</info>
Log file:     $log"));
  }

  /**
   * Checks if the development web server is running
   */
  function serverStatus ()
  {
    $pid = $this->getPID ();
    if ($pid) {
      if (isRunning ($pid))
        $this->io->done ("The server is <info>running</info>
PID: <info>$pid</info>");
      else {
        unlink ($this->PID_FILE);
        $this->io->done ("The server <red>crashed</red>", 2);
      }
    }
    else $this->io->done ("The server is <red>not running</red>", 1);
  }

  /**
   * Stops the development web server
   */
  function serverStop ()
  {
    $pid = $this->getPID ();
    if (!$pid)
      $this->io->error ("The server is already stopped");
    stopProcess ($pid);
    unlink ($this->PID_FILE);
    $this->io->done ("The web server is now <info>stopped</info>");
  }

  /**
   * @return bool|int false if the process is not running.
   */
  private function getPID ()
  {
    $pid = @file_get_contents ($this->PID_FILE);
    return $pid ? intval ($pid) : false;
  }

}
