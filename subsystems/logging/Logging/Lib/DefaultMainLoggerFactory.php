<?php

namespace Electro\Logging\Lib;

use Electro\Interfaces\Logging\MainLoggerFactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class DefaultMainLoggerFactory implements MainLoggerFactoryInterface
{
  function make ()
  {
    $logger = new Logger('main');
    $logger->pushProcessor (new PsrLogMessageProcessor);
//    $logger->pushHandler (new StreamHandler()); //NormalizerFormatter
    return $logger;
  }

}
