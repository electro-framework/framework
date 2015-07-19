<?php
namespace Selene;
use Robo\Task\FileSystem\FilesystemStack;
use Robo\Tasks;
use Selene\Commands\BuildCommands;
use Selene\Commands\CreateCommands;
use Selene\Commands\InitCommands;

/**
 * The preset Selene console commands configuration for Selene's task runner.
 */
class FrameworkTasks extends Tasks
{
  use InitCommands;
  use CreateCommands;
  use BuildCommands;

  /** @var Application */
  protected $app;

  function __construct ()
  {
    global $application, $argc;
    if (!isset($application)) {
      $this->say ("Selene tasks must be run from the 'selene' command.");
      exit (1);
    }
    $this->app = $application;
    $this->stopOnFail ();
  }

  /**
   * Prints an error message and stops execution. Use only on commands, not on tasks.
   * @param string $text  The message.
   * @param int    $width Error box width.
   */
  protected function error ($text, $width = 40)
  {
    if (strlen ($text) < $width - 4)
      $width = strlen ($text) + 4;
    $format = "<fg=white;bg=red;options=bold>%s</fg=white;bg=red;options=bold>";
    $text   = str_pad ($text, $width, ' ', STR_PAD_BOTH);
    $len    = strlen ($text) + 2;
    $space  = str_repeat (' ', $len);
    $this->writeln (sprintf ($format, $space));
    $this->writeln (sprintf ($format, " $text "));
    $this->writeln (sprintf ($format, $space));
    exit (1);
  }

  protected function fs ()
  {
    return new FilesystemStack;
  }

  protected function write ($text)
  {
    $this->getOutput ()->write ($text);
  }

  protected function writeln ($text)
  {
    $this->getOutput ()->writeln ($text);
  }

}
