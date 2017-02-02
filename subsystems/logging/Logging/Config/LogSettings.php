<?php

namespace Electro\Logging\Config;

/**
 * Configuration settings for the logging subsystem.
 */
class LogSettings
{
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
   * Minimum message level that is logged by default; for use with PSR-3 loggers.
   *
   * <p>You may set this value via the `LOG_LEVEL` environment setting.
   *
   * <p>On production, set to the desired level:
   * <p>
   * ```
   * 100 = DEBUG     Detailed debug information
   * 200 = INFO      Interesting events
   * 250 = NOTICE    Uncommon events
   * 300 = WARNING   Exceptional occurrences that are not errors
   * 400 = ERROR     Runtime errors
   * 500 = CRITICAL  Critical condition
   * 550 = ALERT     Action must be taken immediately (ex. entire website down, database unavailable, etc.)
   * 600 = EMERGENCY Urgent alert
   * ```
   * <p>The default setting is 200, which is suitable for development.
   *
   * ><p>**Note:** this is NOT used by console-based applications; use the `-v, -vv and -vvv` command-line options to
   * choose the log level at runtime.
   *
   * @return int
   */
  function getLogLevel ()
  {
    return env ('LOG_LEVEL', 200);
  }

}
