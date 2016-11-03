<?php
namespace Electro\Logging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Kernel\Config\KernelSettings;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Provides a main logger for the application.
 *
 * > <p>To add logging handlers to the main logger, inject a LoggerInterface instance into your class and call
 * pushHandler() on it.
 */
class LoggingModule
{
  static function register (InjectorInterface $injector)
  {
    $injector
      ->share (LoggerInterface::class)
      ->delegate (LoggerInterface::class, function (KernelSettings $kernelSettings) {
        $logger = new Logger ('main');
        if ($kernelSettings->isConsoleBased)
          $logger->pushHandler (new StreamHandler('php://stderr', getenv ('DEBUG_LEVEL') ?: Logger::DEBUG));
        return $logger;
      });
  }

}
