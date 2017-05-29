<?php
namespace Electro\Tasks\Shared\Base;

use Robo\Task\Composer\Base;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerTask extends Base
{
  public function __construct()
  {
    parent::__construct(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'?'C:\\ProgramData\\ComposerSetup\\bin\\composer':null);
  }
  
  function action ($action)
  {
    $this->action = $action;

    return $this;
  }

  public function run ()
  {
    $output = $this->getOutput ();
    // Prevent Composer "bug" that disables output colorization on some circumstances.
    $this->option ($output->isDecorated () ? '--ansi' : '--no-ansi');

    switch ($output->getVerbosity ()) {
      case OutputInterface::VERBOSITY_DEBUG:
        $this->option ('-vvv');
        break;
      case OutputInterface::VERBOSITY_VERY_VERBOSE:
        $this->option ('-vv');
        break;
      case OutputInterface::VERBOSITY_VERBOSE:
        $this->option ('-v');
        break;
      case OutputInterface::VERBOSITY_QUIET:
        $this->option ('-q');
        break;
    }

    $command = $this->getCommand ();
    return $this->executeCommand ($command);
  }

  function setPathToComposer ($path)
  {
    $this->command = $path;
  }

}
