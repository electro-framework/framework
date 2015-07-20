<?php
namespace Selene\Traits;

use Robo\Task\FileSystem\FilesystemStack;
use Selene\Application;

/**
 * @property Application $app
 */
trait CommandAPI
{
  use \Robo\Common\IO;

  protected function app ()
  {
    global $application;

    return $application;
  }

  protected function clear ()
  {
    if ($this->getOutput ()->getFormatter ()->isDecorated ())
      $this->write ("\033[0;0f\033[2J");
  }

  protected function comment ($text)
  {
    $this->say ("<comment>$text</comment>");
  }

  protected function done ($text)
  {
    $this->nl ();
    $this->say ($text);
    $this->nl ();
  }

  /**
   * Prints an error message and stops execution. Use only on commands, not on tasks.
   * @param string $text  The message.
   * @param int    $width Error box width.
   */
  protected function error ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=red', $width, CONSOLE_ALIGN_LEFT);
    exit (1);
  }

  protected function fs ()
  {
    return new FilesystemStack;
  }

  protected function nl ()
  {
    $this->writeln ();
  }

  protected function title ($text)
  {
    $this->writeln ();
    $this->say ("<title>$text</title>" . PHP_EOL);
  }

  protected function write ($text)
  {
    $this->getOutput ()->write ($text);
  }

  protected function writeln ($text = '')
  {
    $this->getOutput ()->writeln ($text);
  }

  protected function yell ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=green;options=bold', $width);
  }

  private function box ($text, $colors, $width = 0, $align = CONSOLE_ALIGN_CENTER)
  {
    $lines = explode (PHP_EOL, $text);
    if (!$width)
      $width = max (array_map ('mb_strlen', $lines));
    $format = "<$colors>%s</$colors>";
    $space  = str_repeat (' ', $width + 4);
    foreach ($lines as $i => $line)
      $lines[$i] = mb_str_pad ($line, $width, ' ', $align);

    $this->writeln (sprintf ($format, $space));
    foreach ($lines as $line)
      $this->writeln (sprintf ($format, "  $line  "));
    $this->writeln (sprintf ($format, $space));
  }

}
