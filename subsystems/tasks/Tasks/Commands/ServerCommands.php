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
   * @option $global|g If specified, the server will serve all sites under the root web directory
   * @option $root|r The root web directory name (not the path). The directory will be searched for starting at the
   *         current directory and going upwards
   */
  function serverStart ($options = [
    'port|p' => 8000, 'address|a' => '127.0.0.1', 'global|g' => false, 'root|r' => 'Sites',
  ])
  {
    $pid = $this->getPID ();
    if ($pid && isRunning ($pid))
      $this->io->error("The server is already started.");
    if ($options['global']) {
      $dir = getcwd ();
      while ($dir) {
        $current = basename ($dir);
        if ($current == $options['root']) break;
        $dir = updir ($dir);
      }
      $root = " -t $dir";
      $msg  = ", serving <info>$dir</info>";
    }
    else $msg = $root = '';
    $where = $options['address'] . ':' . $options['port'];
    $pid   = runBackgroundCommand ("php -S $where$root");
    file_put_contents ($this->PID_FILE, $pid);
    $this->io->writeln (sprintf ("Web server listening on <info>$where</info>$msg"));
  }

  /**
   * Checks if the development web server is running
   */
  function serverStatus ()
  {
    $pid = $this->getPID ();
    if ($pid && isRunning ($pid))
      $this->io->writeln ("The web server is <info>running</info> with PID=<info>$pid</info>");
    else $this->io->error ("The web server is not running.");
  }

  /**
   * Stops the development web server
   */
  function serverStop ()
  {
    $pid = $this->getPID ();
    if (!$pid)
      $this->io->error ("The web server is not running.");
    stopProcess ($pid);
    $this->io->done ("The web server is now stopped.");
    unlink ($this->PID_FILE);
  }

  private function getPID ()
  {
    return @file_get_contents ($this->PID_FILE);
  }

}
