<?php

namespace Electro\Logging\Lib;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Logging\LogCentralInterface;
use Electro\Interfaces\Logging\LoggingSetupInterface;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class DefaultLoggingSetup implements LoggingSetupInterface
{
  /** @var InjectorInterface */
  private $injector;
  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct (InjectorInterface $injector, LoggerFactory $loggerFactory)
  {
    $this->injector      = $injector;
    $this->loggerFactory = $loggerFactory;
  }

  function setup (LogCentralInterface $logCentral)
  {
    $new = $this->loggerFactory;

    $logCentral->loggers ()->register ([
      'general'  => new Logger ('general'),
      'database' => new Logger ('database'),
      'security' => new Logger ('security'),
    ]);
    $logCentral->processors ()->register ([
      '@arg-expand' => new PsrLogMessageProcessor,
      '@request'    => $new->httpRequestProcessor (),
    ]);
    $logCentral->handlers ()->register ([
      '#main'            => $logCentral->handlerGroup ('#log-file', '#console', '#web-console'),
      '#stderr'          => $new->stderrHandler (),
      '#stdout'          => new StreamHandler ('php://stdout', Logger::DEBUG, true, 0644, true),
      '#console'         => $new->consoleHandler (),
      '#web-console'     => $new->webConsoleHandler (),
      '#log-file'        => $new->logFileHandler (),
      '#fingers-crossed' => new FingersCrossedHandler ($logCentral->handler ('#main'), Logger::WARNING),
    ]);
    $logCentral->formatters ()->register ([
      'line'    => $new->defaultFormatter (),
      'html'    => new HtmlFormatter,
      'console' => $new->consoleFormatter (),
    ]);
    $logCentral
      ->connectLoggersToHandlers ([
        'general' => ['#main'],
      ])
      ->connectLoggersToProcessors ([
      ])
      ->connectHandlersToProcessors ([
        '#main' => ['@arg-expand', '@request'],
      ])
      ->assignFormattersToHandlers ([
        'line'    => '#main',
        'html'    => '#web-console',
        'console' => '#console',
      ]);
  }

}
