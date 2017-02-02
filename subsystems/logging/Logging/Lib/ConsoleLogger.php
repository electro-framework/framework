<?php

namespace Electro\Logging\Lib;

use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PSR-3 compliant console logger.
 *
 * <p>This is a customization of the Symfony ConsoleLogger for use on the Electro framework.
 *
 * <p>Additional features:
 * - user-defined log format;
 * - user-defined date/time format;
 * - customized default output verbosity to logging level mappings.
 *
 * @copyright Fabien Potencier <fabien@symfony.com>
 * @author    Cláudio Silva <claudio.silva@impactwave.com>
 * @author    Kévin Dunglas <dunglas@gmail.com>
 */
class ConsoleLogger extends AbstractLogger
{
  /**
   * @var string The formatting tag used for error messages that should be sent to STDERR instead of STDOUT.
   */
  static $ERROR_TAG = 'error';
  /**
   * @var array
   */
  protected $formatLevelMap = [
    LogLevel::EMERGENCY => 'error', // Sent to STDERR
    LogLevel::ALERT     => 'error', // Sent to STDERR
    LogLevel::CRITICAL  => 'error', // Sent to STDERR
    LogLevel::ERROR     => 'error', // Sent to STDERR
    LogLevel::WARNING   => 'warning',
    LogLevel::NOTICE    => 'info',
    LogLevel::INFO      => '',
    LogLevel::DEBUG     => 'comment',
  ];
  protected $logFormat      = '<%1$s>[%2$s] %3$s: %4$s</%1$s>';
  /**
   * @var OutputInterface
   */
  protected $output;
  /**
   * @var array
   */
  protected $verbosityLevelMap = [
    LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::WARNING   => OutputInterface::VERBOSITY_VERBOSE,
    LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERY_VERBOSE,
    LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
    LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
  ];
  /**
   * @var string
   */
  private $dateTimeFormat = '';

  /**
   * @param OutputInterface $output
   * @param array           $verbosityLevelMap
   * @param array           $formatLevelMap
   */
  public function __construct (OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
  {
    $this->output            = $output;
    $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
    $this->formatLevelMap    = $formatLevelMap + $this->formatLevelMap;
  }

  /**
   * {@inheritdoc}
   */
  public function log ($level, $message, array $context = [])
  {
    if (!isset($this->verbosityLevelMap[$level])) {
      throw new InvalidArgumentException(sprintf ('The log level "%s" does not exist.', $level));
    }

    // Write to the error output if necessary and available
    if ($this->formatLevelMap[$level] === self::$ERROR_TAG && $this->output instanceof ConsoleOutputInterface) {
      $output = $this->output->getErrorOutput ();
    }
    else {
      $output = $this->output;
    }

    if ($output->getVerbosity () >= $this->verbosityLevelMap[$level]) {
      $tag    = $this->formatLevelMap[$level];
      $format = $this->logFormat;
      if (!$tag)
        $format = preg_replace ('#\<%1\$s\>|\</%1\$s\>#', '', $format); // Remove empty tags.

      // If there is context data and there are no placeholders on the message, append a dump of the data to the
      // message.
      if ($context && strpos($message, '{') === false)
        $message .= PHP_EOL . getDump ($context);

      $output->writeln (sprintf ($format,
        $tag,
        $this->dateTimeFormat ? date ($this->dateTimeFormat) : '',
        $level,
        $this->interpolate ($message, $context)
      ));
    }
  }

  /**
   * @param string $format Set to '' to disable date/time computation.
   */
  public function setDateTimeFormat ($format)
  {
    $this->dateTimeFormat = $format;
  }

  public function setLogFormat ($format)
  {
    $this->logFormat = $format;
  }

  /**
   * Interpolates context values into the message placeholders
   *
   * @author PHP Framework Interoperability Group
   *
   * @param string $message
   * @param array  $context
   *
   * @return string
   */
  private function interpolate ($message, array $context)
  {
    // build a replacement array with braces around the context keys
    $replace = [];
    foreach ($context as $key => $val) {
      if (!is_array ($val) && (!is_object ($val) || method_exists ($val, '__toString'))) {
        $replace[sprintf ('{%s}', $key)] = $val;
      }
    }

    // interpolate replacement values into the message and return
    return strtr ($message, $replace);
  }
}
