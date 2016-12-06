<?php
namespace Electro\ConsoleApplication\Services;

use Electro\Interfaces\ConsoleIOInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
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
  /** @var bool TRUE until the first line break is output. */
  private $firstLine = true;
  /** @var string */
  private $indent = '';
  /** @var InputInterface */
  private $input;
  /** @var OutputInterface */
  private $output;
  /** @var int Verbosity level before the output was muted. */
  private $prevVerbosity;
  /** @var array A list of width and height. */
  private $terminalSize;

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

  function banner ($text, $width = 0)
  {
    $this->box ($text, 'fg=white;bg=blue', $width);
    return $this;
  }

  function cancel ($message = 'Canceled')
  {
    if ($this->getInput ()->isInteractive ())
      $this->error ($message);
    exit (1);
  }

  function clear ()
  {
    if ($this->output && $this->output->getFormatter ()->isDecorated ())
      $this->write ("\033[0;0f\033[2J");
    return $this;
  }

  function comment ($text)
  {
    return $this->say ("<comment>$text</comment>");
  }

  function confirm ($question)
  {
    return $this->doAsk (new ConfirmationQuestion($this->formatQuestion ($question . ' (y/n)'), false));
  }

  function done ($text = '', $dontExit = false)
  {
    if (strlen ($text)) {
      if (!$this->firstLine)
        $this->nl ();
      $this->say ($text);
    }
    $this->nl ();
    if (!$dontExit)
      exit (0);
  }

  function error ($text, $width = 0, $status = 1)
  {
    $this->box ($text, 'fg=white;bg=red', $width, CONSOLE_ALIGN_LEFT);
    exit ($status);
  }

  function getDialog ()
  {
    return new QuestionHelper();
  }

  function getInput ()
  {
    return $this->input;
  }

  function setInput (InputInterface $input)
  {
    $this->input = $input;
  }

  function getOutput ()
  {
    //return Config::get ('output', new NullOutput());
    return $this->output;
  }

  function setOutput (OutputInterface $output)
  {
    $this->output = $output;
  }

  function indent ($level = 0)
  {
    $this->indent = str_repeat (' ', $level);
    return $this;
  }

  function menu ($question, array $options, $defaultIndex = -1, array $secondColumn = null,
                 callable $validator = null)
  {
    if (!$this->getInput ()->isInteractive ())
      return $defaultIndex;
    $pad   = strlen (count ($options));
    $width = empty ($options) ? 0 : max (array_map ('taggedStrLen', $options));
    $this->nl ()->writeln ("<question>$question</question>")->nl ();
    foreach ($options as $i => $option) {
      $this->write ("    <fg=cyan>" . str_pad ($i + 1, $pad, ' ', STR_PAD_LEFT) . ".</fg=cyan> ");
      $this->writeln (isset($secondColumn)
        ? taggedStrPad ($option, $width) . "  $secondColumn[$i]"
        : $option
      );
    }
    $this->nl ();
    do {
      $a = $defaultIndex < 0 ? $this->ask ('') : $this->askDefault ('', $defaultIndex + 1);
      if (!$a)
        if ($defaultIndex < 0) return -1;
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

    $this->nl ();
    return $i - 1;
  }

  function mute ()
  {
    $this->prevVerbosity = $this->output->getVerbosity ();
    $this->output->setVerbosity (Output::VERBOSITY_QUIET);
    return $this;
  }

  function nl ()
  {
    $this->writeln ();
    return $this;
  }

  function say ($text)
  {
    return $this->writeln ($text);
  }

  function setColor ($name, $style)
  {
    if ($this->output)
      $this->output->getFormatter ()->setStyle ($name, $style);
    return $this;
  }

  function table (array $headers, array $data, array $widths, array $align = null)
  {
    self::adjustColumnWidths ($widths);

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

  public function terminalSize (array $terminalSize = null)
  {
    if ($terminalSize)
      $this->terminalSize = $terminalSize;
    return $this->terminalSize;
  }

  function title ($text)
  {
    $this->nl ()->writeln ("<title>$text</title>")->nl ();
    return $this;
  }

  function unmute ()
  {
    $this->output->setVerbosity ($this->prevVerbosity);
    return $this;
  }

  function warn ($text)
  {
    if ($this->output)
      $this->output->writeln ("<warning> $text </warning>");
    return $this;
  }

  function write ($text)
  {
    if ($this->indent)
      $text = preg_replace ('/^/m', $this->indent, $text);
    if ($this->output)
      $this->output->write ($text);
    return $this;
  }

  function writeln ($text = '')
  {
    if ($this->output->getVerbosity() != Output::VERBOSITY_QUIET) {
      $this->firstLine = false;
      if ($this->indent)
        $text = preg_replace ('/^/m', $this->indent, $text);
      if ($this->output)
        $this->output->writeln ($text);
    }
    return $this;
  }

  /**
   * Computes the width of columns that are set to 0 width (which means: auto).
   *
   * <p>Each width that is 0 will be assigned the remaining horizontal space on the terminal, divided by the number of
   * columns set to 0.
   *
   * @param array $widths
   */
  protected function adjustColumnWidths (array &$widths)
  {
    $t  = $c = 0;
    $li = -1;
    foreach ($widths as $i => $w)
      if ($w) $t += $w + 2;
      else {
        ++$c;
        if ($i > $li) $li = $i;
      }
    $t += 1;
    if ($c) {
      $maxW      = $this->terminalSize[0];
      $remaining = $maxW - $t;
      $f         = floor ($remaining / $c) - 2;
      if ($f < 2) $f = 2;
      $adjust = $remaining - ($f + 2) * $c;
      if ($adjust < 0) $adjust = 0;
      foreach ($widths as $i => &$w) {
        if (!$w) $w = $f + ($i == $li ? $adjust : 0);
      }
    }
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
    if ($this->input && $this->output)
      return $this->getDialog ()->ask ($this->input, $this->output, $question);
  }

  private function formatQuestion ($message)
  {
    return "<question>$message</question> ";
  }

}
