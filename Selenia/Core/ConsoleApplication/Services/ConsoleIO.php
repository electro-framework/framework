<?php
namespace Selenia\Core\ConsoleApplication\Services;

use InvalidArgumentException;
use Selenia\Interfaces\ConsoleIOInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

define ('CONSOLE_ALIGN_CENTER', STR_PAD_BOTH);
define ('CONSOLE_ALIGN_LEFT', STR_PAD_RIGHT);
define ('CONSOLE_ALIGN_RIGHT', STR_PAD_LEFT);

/**
 * A service that provides input and output from/to the terminal
 */
class ConsoleIO implements ConsoleIOInterface
{
  private $input;
  private $output;
  private $warnings = [];

  private static function tabular (array $data, array $widths, array $align = null, $glue = ' ',
                                   $pad = ' ', $marker = '…')
  {
    $out = [];
    if (empty($align))
      $align = [];
    foreach ($widths as $i => $w)
      switch (get ($align, $i, 'L')) {
        case 'L':
          $align[$i] = STR_PAD_RIGHT;
          break;
        case 'R':
          $align[$i] = STR_PAD_LEFT;
          break;
        case 'C':
          $align[$i] = STR_PAD_BOTH;
          break;
        default:
          throw new InvalidArgumentException ("Invalid value for align: $align[$i]");
      }
    foreach ($widths as $i => $w) {
      $s = strval (get ($data, $i));
      $l = taggedStrLen ($s);
      if ($l > $w) {
        $out[] = taggedStrCrop ($s, $w, $marker);
//        echo taggedStrLen (taggedStrCrop ($s, $w - mb_strlen ($overflow, 'UTF-8')) . $overflow);

      }
      else $out[] = taggedStrPad ($s, $w, $align[$i], $pad);
    }
    return implode ($glue, $out);
  }

  function ask ($question, $hideAnswer = false)
  {
    if ($hideAnswer) {
      return $this->askHidden ($question);
    }
    return $this->doAsk (new Question($this->formatQuestion ($question)));
  }

  function askDefault ($question, $default)
  {
    return $this->doAsk (new Question($this->formatQuestion ("$question [$default]"), $default));
  }

  function askHidden ($question)
  {
    $question = new Question($this->formatQuestion ($question));
    $question->setHidden (true);
    return $this->doAsk ($question);
  }

  /**
   * @param string $text
   * @param int    $width 0 = autofit
   * @return $this
   */
  function banner ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=blue', $width);
    return $this;
  }

  /**
   * @return $this
   */
  function clear ()
  {
    if ($this->getOutput ()->getFormatter ()->isDecorated ())
      $this->write ("\033[0;0f\033[2J");
    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  function comment ($text)
  {
    return $this->say ("<comment>$text</comment>");
  }

  /**
   * @param string $question
   * @return bool
   */
  function confirm ($question)
  {
    return $this->doAsk (new ConfirmationQuestion($this->formatQuestion ($question . ' (y/n)'), false));
  }

  function done ($text)
  {
    $this->nl ()->say ("<info>$text</info>")->nl ();
    if (!empty($this->warnings))
      $this->writeln (implode (PHP_EOL, $this->warnings))->nl ();
  }

  /**
   * Prints an error message and stops execution. Use only on commands, not on tasks.
   *
   * @param string $text  The message.
   * @param int    $width Error box width.
   */
  function error ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=red', $width, CONSOLE_ALIGN_LEFT);
    exit (1);
  }

  function getDialog ()
  {
    return new QuestionHelper();
  }

  /**
   * @return InputInterface
   */
  function getInput ()
  {
    return $this->input;
  }

  function setInput (InputInterface $input)
  {
    $this->input = $input;
  }

  /**
   * @return OutputInterface
   */
  function getOutput ()
  {
    //return Config::get ('output', new NullOutput());
    return $this->output;
  }

  function setOutput (OutputInterface $output)
  {
    $this->output = $output;
  }

  /**
   * Presents a list to the user, from which he/she must select an item.
   *
   * @param string   $question
   * @param string[] $options
   * @param int      $defaultIndex The default answer if the user just presses return. -1 = no default (empty input is
   *                               not allowed.
   * @param array    $secondColumn If specified, it contains the 2nd column for each option.
   * @param callable $validator    If specified, a function that validates the user's selection.
   *                               It receives the selected index (0 based) as argument and it should return `true`
   *                               if the selection is valid or an error message string if not.
   * @return int The selected index (0 based).
   */
  function menu ($question, array $options, $defaultIndex = -1, array $secondColumn = null,
                 callable $validator = null)
  {
    $pad   = strlen (count ($options));
    $width = empty ($options) ? 0 : max (array_map ('taggedStrLen', $options));
    $this->nl ()->writeln ("<question>$question</question>")->nl ();
    foreach ($options as $i => $option) {
      $this->write ("    <fg=blue>" . str_pad ($i + 1, $pad, ' ', STR_PAD_LEFT) . ".</fg=blue> ");
      $this->writeln (isset($secondColumn)
        ? taggedStrPad ($option, $width) . "  $secondColumn[$i]"
        : $option
      );
    }
    $this->nl ();
    do {
      $a = $defaultIndex < 0 ? $this->ask ('') : $this->askDefault ('', $defaultIndex + 1);
      $i = intval ($a);
      if ($i < 1 || $i > count ($options)) {
        $this->say ("<error>Please select a number from the list</error>");
        $a = 0;
        continue;
      }
      else if (isset($validator)) {
        $r = $validator($i - 1);
        if ($r !== true) {
          $this->say ("<error>$r</error>");
          $a = 0;
          continue;
        }
      }
    } while (!$a);

    return $i - 1;
  }

  /**
   * @return $this
   */
  function nl ()
  {
    $this->writeln ();
    return $this;
  }

  /**
   * Alias of `writeln()`.
   *
   * @param string $text
   * @return $this
   */
  function say ($text)
  {
    return $this->writeln ($text);
  }

  /**
   * Defines a tag for a custom color.
   *
   * @param string                        $name
   * @param OutputFormatterStyleInterface $style
   * @return $this
   */
  function setColor ($name, $style)
  {
    $this->getOutput ()->getFormatter ()->setStyle ($name, $style);
    return $this;
  }

  /**
   * Outputs data in a tabular format.
   *
   * @param string[]      $headers
   * @param array         $data
   * @param int[]         $widths
   * @param string[]|null $align
   */
  function table (array $headers, array $data, array $widths, array $align = null)
  {
    $this->writeln ('┌─' . self::tabular ([], $widths, null, '┬─', '─') . '┐');
    $this->writeln ('│ <comment>' . self::tabular ($headers, $widths, null, '</comment>│ <comment>') .
                    '</comment>│');
    $this->writeln ('├─' . self::tabular ([], $widths, null, '┼─', '─') . '┤');
    foreach ($data as $s) {
      $row = array_values ($s);
      $this->writeln ('│ ' . self::tabular ($row, $widths, $align, '│ ') . '│');
    }
    $this->writeln ('└─' . self::tabular ([], $widths, null, '┴─', '─') . '┘');
  }

  /**
   * @param string $text
   * @return $this
   */
  function title ($text)
  {
    $this->writeln ();
    $this->say ("<title>$text</title>" . PHP_EOL);
    return $this;
  }

  /**
   * Add a warning message to be displayed later, when `done()` is called.
   *
   * @param string $text
   * @return $this
   */
  function warn ($text)
  {
    $this->warnings[] = "Warning: <warning>$text</warning>";
    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  function write ($text)
  {
    $this->getOutput ()->write ($text);
    return $this;
  }

  /**
   * @param string $text
   * @return $this
   */
  function writeln ($text = '')
  {
    $this->getOutput ()->writeln ($text);
    return $this;
  }

  private function box ($text, $colors, $width = 0, $align = CONSOLE_ALIGN_CENTER)
  {
    $lines = explode (PHP_EOL, $text);
    if (!$width)
      $width = max (array_map ('taggedStrLen', $lines));
    $format = "<$colors>%s</$colors>";
    $space  = str_repeat (' ', $width + 4);
    foreach ($lines as $i => $line)
      $lines[$i] = taggedStrPad ($line, $width, $align);

    $this->writeln (sprintf ($format, $space));
    foreach ($lines as $line)
      $this->writeln (sprintf ($format, "  $line  "));
    $this->writeln (sprintf ($format, $space));
  }

  private function doAsk (Question $question)
  {
    return $this->getDialog ()->ask ($this->getInput (), $this->getOutput (), $question);
  }

  private function formatQuestion ($message)
  {
    return "<question>$message</question> ";
  }

}
