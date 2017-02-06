<?php

namespace Electro\Logging\Lib;

use Auryn\InjectionException;
use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Logging\Config\LogSettings;
use Electro\Logging\Lib\Formatters\ConsoleFormatter;
use Electro\Logging\Lib\Handlers\ConsoleHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;

/**
 * A factory for the framework's standard loggers, handlers, processors and formatters.
 */
class LoggerFactory
{
  /** @var InjectorInterface */
  private $injector;
  /** @var KernelSettings */
  private $kernelSettings;
  /** @var LogSettings */
  private $logSettings;

  public function __construct (KernelSettings $kernelSettings, InjectorInterface $injector, LogSettings $logSettings)
  {
    $this->kernelSettings = $kernelSettings;
    $this->injector       = $injector;
    $this->logSettings    = $logSettings;
  }

  /**
   * Creates a `ConsoleFormatter` with settings defined by `LogSettings`.
   *
   * @return ConsoleFormatter|LineFormatter
   * @throws InjectionException
   */
  function consoleFormatter ()
  {
//    if (!$this->kernelSettings->isConsoleBased) {
//      $consoleIO = $this->injector->make (ConsoleIOInterface::class);
//      return new ConsoleFormatter($consoleIO->getOutput (), $this->logSettings);
//    }
    return new LineFormatter;
  }

  /**
   * Creates a `ConsoleHandler` with settings defined by `LogSettings`, or a `NullHandler` if the app is not running on
   * console mode.
   *
   * @return ConsoleHandler|NullHandler
   * @throws InjectionException
   */
  function consoleHandler ()
  {
//    if ($this->kernelSettings->isConsoleBased) {
//      $consoleIO = $this->injector->make (ConsoleIOInterface::class);
//      return new ConsoleHandler ($consoleIO->getOutput (), $this->logSettings);
//    }
    return new NullHandler;
  }

  /**
   * Creates a plain-text `LineFormatter` with settings defined by `LogSettings`.
   *
   * @return LineFormatter
   */
  function defaultFormatter ()
  {
    return new LineFormatter ($this->logSettings->messageFormat, $this->logSettings->dateTimeFormat);
  }

  /**
   * Creates a processor that runs a {@see WebProcessor} bound to the current HTTP request, if the application is
   * running as a wev server.
   *
   * <p>When the logging subsystem is initialized, the `ServerRequest` instance has not been initialized yet, so we
   * cannot instantiated the target processor yet. Instead, another processor is built that will create and run the true
   * processor later when it is invoked, if the request is available at that time, otherwise it will return the input
   * record unmodified.
   *
   * @return callable
   * @throws InjectionException
   */
  function httpRequestProcessor ()
  {
    return $this->kernelSettings->isWebBased
      ? function (array $record) {
        /** @var CurrentRequestInterface $currentRequest */
        $currentRequest = $this->injector->make (CurrentRequestInterface::class);
        $request        = $currentRequest->get ();
        if (!$request)
          return $record;
        $proc = new WebProcessor($request->getServerParams (), $this->logSettings->logRequestFields);
        return $proc ($record);
      }
      : identity ();
  }

  /**
   * Creates a handler that, depending on the logging configuration settings, does one of the following:
   * 1. writes to a single log file, or
   * - writes to a set of rotating log files, or
   * - does nothing if file logging is disabled.
   *
   * @return StreamHandler|NullHandler
   */
  public function logFileHandler ()
  {
    $settings = $this->logSettings;
    if ($settings->enableFileLogger) {
      $fname =
        "{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->storagePath}/logs/{$settings->logFileName}";
      if ($settings->enableLogRotation) {
        $handler = new RotatingFileHandler($fname, 0, Logger::DEBUG, true, null, true);
        $handler->setFilenameFormat ($settings->logFileNameFormat, $settings->logFileDateFormat);
        return $handler;
      }
      return new StreamHandler($fname, Logger::DEBUG, true, null, true);
    }
    return new NullHandler;
  }

  /**
   * Creates a handler that outputs to STDERR.
   *
   * @return StreamHandler
   */
  function stderrHandler ()
  {
    return new StreamHandler ('php://stderr', Logger::DEBUG, true, 0644, true);
  }

  /**
   * Creates a handler that outputs messages to the web console if the console is enabled.
   *
   * @return NullHandler|WebConsoleMonologHandler
   * @throws InjectionException
   */
  public function webConsoleHandler ()
  {
    $level = $this->logSettings->webConsoleLogLevel;
    /** @var DebugSettings $debugSettings */
    $debugSettings = $this->injector->make (DebugSettings::class);
    return $debugSettings->webConsole ? new WebConsoleMonologHandler ($level) : new NullHandler ($level);
  }

}
