<?php

namespace Electro\Logging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\Logging\LogCentralInterface;
use Electro\Interfaces\Logging\MainLoggerFactoryInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Logging\Lib\DefaultMainLoggerFactory;
use Electro\Logging\Services\LogCentral;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * This subsystem provides a main logger for the application.
 *
 * <p>To use the main logger, inject a `LoggerInterface` instance into your class; this way your code is not bound to
 * Monolog and can use any PSR-3 compatible logger.
 *
 * <p>To add logging handlers to the main logger, inject a Monolog `Logger` instance into your class and call
 * `pushHandler()` on it.
 */
class LoggingModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ConsoleProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    // This module runs before all other modules, so that it becomes enabled as soon as possible.
    $kernel->onPreRegister (
      function (InjectorInterface $injector) {
        $injector
          ->share (LoggerInterface::class)
          ->share (LogSettings::class)
          //
          // The logger registry.
          //
          ->alias (LogCentralInterface::class, LogCentral::class)
          ->share (LogCentralInterface::class)
          //
          // The main Monolog logger, which can also be retrieved as a generic PSR-3 logger.
          //
          ->share (Logger::class)
          ->delegate (Logger::class, function (MainLoggerFactoryInterface $factory) {
            return $factory->make ();
          })
          ->alias (LoggerInterface::class, Logger::class)
          //
          // Define the default logging configurator; it may be overridden later.
          //
          ->alias (MainLoggerFactoryInterface::class, DefaultMainLoggerFactory::class);
      });
  }

}
