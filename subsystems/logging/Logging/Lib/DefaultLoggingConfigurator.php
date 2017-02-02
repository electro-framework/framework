<?php

namespace Electro\Logging\Lib;

use Electro\Interfaces\Http\LoggingConfiguratorInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class DefaultLoggingConfigurator implements LoggingConfiguratorInterface
{

  function configure (Logger $logger)
  {
    $logger->pushProcessor (new PsrLogMessageProcessor);
    $logger->pushHandler(new StreamHandler());//NormalizerFormatter
  }

}
