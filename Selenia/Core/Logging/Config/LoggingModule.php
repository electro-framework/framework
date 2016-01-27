<?php
namespace Selenia\Core\Logging\Config;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

/**
 * Provides a main logger for the application.
 *
 * > <p>To add logging handlers to the main logger, inject a LoggerInterface instance into your class and call
 * pushHandler() on it.
 */
class LoggingModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (LoggerInterface::class)
      ->delegate (LoggerInterface::class, function (Application $app) {
        $logger = new Logger ('main');
        if ($app->isConsoleBased)
          $logger->pushHandler (new StreamHandler('php://stderr', getenv ('DEBUG_LEVEL') ?: Logger::DEBUG));
        return $logger;
      });
  }

}
