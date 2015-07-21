<?php
namespace Selene\Traits;

use Robo\Task\FileSystem\FilesystemStack;
use Selene\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This 'trait interface' declares methods from `CommandAPI` and from the imherited `Robo\Common\IO`.
 */
trait CommandAPIInterface
{
  /**
   * @return Application
   */
  abstract protected function app ();

  abstract protected function ask ($question, $hideAnswer = false);

  abstract protected function askDefault ($question, $default);

  abstract protected function askHidden ($question);

  /**
   * @param string $text
   * @param int    $width 0 = autofit
   * @return $this
   */
  abstract protected function banner ($text, $width = 0);

  /**
   * @return $this
   */
  abstract protected function clear ();

  /**
   * @param string $text
   * @return $this
   */
  abstract protected function comment ($text);

  abstract protected function confirm ($question);

  abstract protected function done ($text);

  /**
   * Prints an error message and stops execution. Use only on commands, not on tasks.
   * @param string $text  The message.
   * @param int    $width Error box width.
   */
  abstract protected function error ($text, $width = 0);

  /**
   * @return FilesystemStack
   */
  abstract protected function fs ();

  abstract protected function getDialog ();

  /**
   * @return InputInterface
   */
  abstract protected function getInput ();

  /**
   * @return OutputInterface
   */
  abstract protected function getOutput ();

  /**
   * Presents a list to the user, from which he/she must select an item.
   * @param string   $question
   * @param string[] $options
   * @param int      $defaultIndex The default answer if the user just presses return. -1 = no default (empty input is
   *                               not allowed.
   * @return int The selected index (0 based).
   */
  abstract protected function menu ($question, array $options, $defaultIndex = -1);

  /**
   * @return $this
   */
  abstract protected function nl ();

  abstract protected function say ($text);

  /**
   * @param string $text
   * @return $this
   */
  abstract protected function title ($text);

  /**
   * @param string $text
   * @return $this
   */
  abstract protected function write ($text);

  /**
   * @param string $text
   * @return $this
   */
  abstract protected function writeln ($text = '');

  abstract protected function yell ($text, $length = 0);

}
