<?php
namespace Selene\Traits;

use Robo\Task\FileSystem\FilesystemStack;
use Selene\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property Application $app
 */
trait CommandAPI
{
  /**
   * Prints an error message and stops execution. Use only on commands, not on tasks.
   * @param string $text  The message.
   * @param int    $width Error box width.
   */
  abstract protected function error ($text, $width = 40);

  abstract protected function write ($text);

  abstract protected function writeln ($text);

  /**
  * @return OutputInterface
  */
  abstract protected function getOutput();

  /**
   * @return InputInterface
   */
  abstract protected function getInput();

  abstract protected function say($text);

  abstract protected function yell($text, $length = 40);

  abstract protected function ask($question, $hideAnswer = false);

  abstract protected function askHidden($question);

  abstract protected function askDefault($question, $default);

  abstract protected function confirm($question);

  abstract protected function getDialog();

  /**
   * @return FilesystemStack
   */
  abstract protected function fs();

}
