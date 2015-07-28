<?php
namespace Selene;
use Robo\Tasks;
use Selene\Commands\BuildCommands;
use Selene\Commands\InitCommands;
use Selene\Commands\ModuleCommands;
use Selene\Traits\CommandAPI;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * The preset Selene console commands configuration for Selene's task runner.
 */
class CoreCommands extends Tasks
{
  use CommandAPI;
  use InitCommands;
  use BuildCommands;
  use ModuleCommands;

  function __construct ()
  {
    global $application;
    if (!isset($application)) {
      $this->say ("Selene tasks must be run from the 'selene' command");
      exit (1);
    }
    $this->stopOnFail ();
    $this->customizeColors ();
  }

  private function customizeColors ()
  {
    $this->setColor ('title', new OutputFormatterStyle ('magenta'));
    $this->setColor ('question', new OutputFormatterStyle ('cyan'));
    $this->setColor ('warning', new OutputFormatterStyle ('red', 'yellow'));
  }

  private function setColor ($name, $style)
  {
    $this->getOutput ()->getFormatter ()->setStyle ($name, $style);
  }

}
