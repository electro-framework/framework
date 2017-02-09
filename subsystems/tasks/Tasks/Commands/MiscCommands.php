<?php

namespace Electro\Tasks\Commands;

use Electro\Caching\Config\CachingSettings;
use Electro\Configuration\Lib\IniFile;
use Electro\ConsoleApplication\ConsoleApplication;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Config\KernelSettings;
use Robo\Task\FileSystem\CleanDir;
use Robo\Task\FileSystem\FilesystemStack;

/**
 * Implements the Electro Task Runner's pre-set build commands.
 *
 * @property KernelSettings     $app
 * @property ConsoleIOInterface $io
 * @property FilesystemStack    $fs
 * @property CachingSettings    $cachingSettings
 * @property ConsoleApplication $consoleApp
 */
trait MiscCommands
{
  /**
   * Cache management
   * Use this command to enable or disable the caching subsystem, or to clear all cache contents.
   *
   * @param string $subcommand clear | enable | disable | status
   * @throws \Exception
   */
  function cache ($subcommand = null)
  {
    switch ($subcommand) {
      case '':
        $this->consoleApp->run ('help', ['cache']);
        break;
      case 'enable':
        (new IniFile('.env'))->load ()->set ('CACHING', 'true')->save ();
        $this->io->writeln ("Caching: <info>enabled</info>");
        break;
      case 'disable':
        (new IniFile('.env'))->load ()->set ('CACHING', 'false')->save ();
        $this->io->writeln ("Caching: <info>disabled</info>");
        break;
      case 'status':
        $file = (new IniFile('.env'))->load ();
        $v    = strtolower ($file->get ('CACHING'));
        $v    = $v == 'true' ? 'enabled' : ($v == 'false' ? 'disabled' : 'not set');
        $this->io->writeln (sprintf ("Caching: <info>%s</info>", $v));
        break;
      case 'clear':
        $target = $this->kernelSettings->storagePath . DIRECTORY_SEPARATOR . $this->cachingSettings->cachePath;
        (new CleanDir($target))->run ();
        if (!$this->nestedExec)
          $this->io->done ("Cache contents cleared");
        break;
      default:
        $this->io->error ("Invalid sub-command $subcommand");
    }
  }

}
