<?php

namespace Electro\Logging\Lib\Formatters;

use Electro\Logging\Config\LogSettings;
use Monolog\Formatter\FormatterInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A log formatter for a Symfony Console output target.
 *
 * <p>Features:
 * - user-defined log format;
 * - user-defined date/time format;
 * - user-defined [output verbosity -> logging level] mappings;
 * - colorizes the output if it supports color;
 * - user-defined [log level -> color tag] mappings;
 *
 * >Note: this is an adaptation from Symfony's ConsoleLogger.
 */
class ConsoleFormatter implements FormatterInterface
{
  /**
   * @var string The formatting tag used for error messages that should be sent to STDERR instead of STDOUT.
   */
  static $ERROR_TAG = 'error';

  /** @var string[] */
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
   * @var string
   */
  private $dateTimeFormat = '';

  public function __construct (OutputInterface $output, LogSettings $logSettings)
  {
    $this->output         = $output;
    $this->logFormat      = $logSettings->consoleLogFormat;
    $this->dateTimeFormat = $logSettings->dateTimeFormat;
  }

  /**
   * Formats a log record.
   *
   * @param  array $record A record to format
   * @return mixed The formatted record
   */
  public function format (array $record)
  {
    $message = $record['message'];
    $level   = $record['level'];

    $tag    = $this->formatLevelMap[$level];
    $format = $this->logFormat;
    if (!$tag)
      $format = preg_replace ('#\<%1\$s\>|\</%1\$s\>#', '', $format); // Remove empty tags.

    return sprintf ($format,
      $tag,
      $this->dateTimeFormat ? date ($this->dateTimeFormat) : '',
      $level,
      $message
    );
  }

  /**
   * Formats a set of log records.
   *
   * @param  array $records A set of records to format
   * @return mixed The formatted set of records
   */
  function formatBatch (array $records)
  {
    return map ($records, [$this, 'format']);
  }

  /**
   * Sets the date and time format for log messages.
   *
   * <p>Set to `''` to disable date/time computation.
   *
   * @param string $format A standard PHP date format string.
   * @return $this
   */
  function setDateTimeFormat ($format)
  {
    $this->dateTimeFormat = $format;
    return $this;
  }

  /**
   * @param array $formatLevelMap
   * @return $this
   */
  function setFormatLevelMap (array $formatLevelMap = [])
  {
    $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    return $this;
  }

  /**
   * Defines how log messages sent to the console are formatted, in {@see sprintf} format.
   *
   * <p>Use the following placeholders:
   *     %1$s - format tag
   *     %2$s - date and time
   *     %3$s - level
   *     %4$s - message
   *
   * <p>Ex:
   * <p>`'<%1$s>%3$s</%1$s>'` is the default value; no level and no date/time.
   * <p>`'<%1$s>[%2$s] %3$s: %4$s</%1$s>'` is the standard log format for log files, which includes all information.
   *
   * @param string $format
   * @return $this
   */
  function setLogFormat ($format)
  {
    $this->logFormat = $format;
    return $this;
  }

}
