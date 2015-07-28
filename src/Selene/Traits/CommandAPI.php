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

  private $warnings = [];

  protected function app ()
  {
    global $application;

    return $application;
  }

  /**
   * @param string $text
   * @param int    $width 0 = autofit
   * @return $this
   */
  protected function banner ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=blue', $width);

    return $this;
  }

  /**
   * @return $this
   */
  protected function clear ()
  {
    if ($this->getOutput ()->getFormatter ()->isDecorated ())
      $this->write ("\033[0;0f\033[2J");
  }

  /**
   * @param string $text
   * @return $this
   */
  protected function comment ($text)
  {
    $this->say ("<comment>$text</comment>");
  }

  protected function done ($text)
  {
    $this->nl ();
    $this->say ($text);
    $this->nl ();
    if (!empty($this->warnings))
      $this->writeln (implode (PHP_EOL, $this->warnings))->nl ();
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

  /**
   * Presents a list to the user, from which he/she must select an item.
   * @param string   $question
   * @param string[] $options
   * @param int      $defaultIndex The default answer if the user just presses return. -1 = no default (empty input is
   *                               not allowed.
   * @param array    $secondColumn If specified, it contains the 2nd column for each option.
   * @return int The selected index (0 based).
   */
  protected function menu ($question, array $options, $defaultIndex = -1, array $secondColumn = null)
  {
    $pad   = strlen (count ($options));
    $width = max (array_map ('strlen', $options));
    $this->nl ()->writeln ("<question>$question</question>")->nl ();
    foreach ($options as $i => $option) {
      $this->write ("\t<info>" . str_pad ($i + 1, $pad, ' ', STR_PAD_LEFT) . ".</info> ");
      $this->writeln (isset($secondColumn)
        ? mb_str_pad ($option, $width) . "  $secondColumn[$i]"
        : " $option"
      );
    }
    $this->nl ();
    do {
      $a = $defaultIndex < 0 ? $this->ask ('') : $this->askDefault ('', $defaultIndex + 1);
      $i = intval ($a);
      if ($i < 1 || $i > count ($options)) {
        $a = '';
        $this->say ("<error>Please select a number from the list</error>");
      }
    } while (!$a);

    return $i - 1;
  }

  /**
   * @return $this
   */
  protected function nl ()
  {
    $this->writeln ();

    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  protected function title ($text)
  {
    $this->writeln ();
    $this->say ("<title>$text</title>" . PHP_EOL);

    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  protected function warn ($text)
  {
    $this->warnings[] = "Warning: <warning>$text</warning>";
  }

  /**
   * @param string $text
   * @return $this
   */
  protected function write ($text)
  {
    $this->getOutput ()->write ($text);

    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  protected function writeln ($text = '')
  {
    $this->getOutput ()->writeln ($text);

    return $this;
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
