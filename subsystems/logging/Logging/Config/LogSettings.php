<?php

namespace Electro\Logging\Config;

/**
 * Configuration settings for the logging subsystem.
 */
class LogSettings
{
  const FILE_PER_DAY   = 'Y-m-d';
  const FILE_PER_MONTH = 'Y-m';
  const FILE_PER_YEAR  = 'Y';

  /**
   * Defines how log messages sent to the command-line console are formatted, in {@see sprintf} format.
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
   * @var string
   */
  public $consoleLogFormat = '<%1$s>%4$s</%1$s>';

  /**
   * The date and time format for log messages.
   *
   * <p>Set to `''` to disable date/time computation.
   *
   * @var string
   */
  public $dateTimeFormat = 'Y-m-d h:i:s';
  /**
   * @var bool TRUE to enable saving log messages to disk.
   */
  public $enableFileLogger = true;
  /**
   * @var bool TRUE to enable the generation of multiple log files on a schedule, instead of a single log file.
   */
  public $enableLogRotation = true;
  /**
   * A map of [PSR-3 log levels => formatting tags]. Entries from this map will override the default mapping.
   *
   * @var array
   */
  public $formatLevelMap = [];
  /**
   * The date format for the generated log file name. This also determines how often new log files are created; it
   * defaults to one file per day.
   *
   * <p>You can use one of the predefined constants on this class: `FILE_PER_DAY, FILE_PER_MONTH` or `FILE_PER_YEAR`.
   *
   * @var string
   */
  public $logFileDateFormat = 'Y-m-d';
  /**
   * The name of the log file, or the prefix for generated names when using rotating logs.
   *
   * <p>It should be a name without path; the files will be stored on the default logs directory.
   *
   * @var string
   */
  public $logFileName = 'log';
  /**
   * A format string that defines how to generated names for log files when using log rotation.
   *
   * @var string
   */
  public $logFileNameFormat = '{filename}-{date}';
  /**
   * Field names and the related key inside the request's *server params* (the equivalent to `$_SERVER`) to be added to
   * log messages handled by the processor created by {@see LoggerFactory::httpRequestProcessor}.
   *
   * <p>If not provided, it defaults to: `url, ip, http_method, server, referrer`.
   *
   * @var array
   */
  public $logRequestFields = null;
  /**
   * The format of log messages when using the default formatter {@see LineFormatter}.
   *
   * <p>Leave it as NULL to use the default format:
   *
   *     "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
   *
   * @var string|null
   */
  public $messageFormat = null;
  /**
   * A map of [PSR-3 log levels => console verbosity levels]. Entries from this map will override the default mapping.
   *
   * @var array
   */
  public $verbosityLevelMap = [];
  /**
   * Minimum message level that is logged on the web console.
   *
   * <p>You may set this value via the `LOG_LEVEL` environment setting.
   *
   * <p>The levels relevant for debugging are:
   * <p>
   * ```
   * 100 = DEBUG     Detailed debug information
   * 200 = INFO      Interesting events
   * 250 = NOTICE    Uncommon events
   * 300 = WARNING   Exceptional occurrences that are not errors
   * 400 = ERROR     Runtime errors
   * ```
   * ><p>**Note:** this setting is not relevant for production, as the web console will be disabled.
   *
   * @return int
   */
  public $webConsoleLogLevel;

  public function __construct ()
  {
    $this->webConsoleLogLevel = env ('LOG_LEVEL', 200);
  }

}
