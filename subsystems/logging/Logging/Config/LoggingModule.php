<?php

namespace Electro\Logging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\Logging\LogCentralInterface;
use Electro\Interfaces\Logging\LoggingSetupInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Logging\Lib\DefaultLoggingSetup;
use Electro\Logging\Services\LogCentral;
use Electro\Profiles\ApiProfile;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use Psr\Log\LoggerInterface;
use const Electro\Interfaces\Logging\LOG_GENERAL;

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
    return [WebProfile::class, ConsoleProfile::class, ApiProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    // This module runs before all other modules, so that it becomes enabled as soon as possible.
    $kernel
      ->onPreRegister (
        function (InjectorInterface $injector) {
          $injector
            //
            // The main Monolog logger, which can also be retrieved as a generic PSR-3 logger.
            // It's an alias of the 'general' channel defined on LogCentral.
            //
            ->share (LoggerInterface::class)
            ->delegate (LoggerInterface::class, function (LogCentralInterface $logCentral) {
              return $logCentral->loggers ()->get (LOG_GENERAL);
            })
            //
            ->share (LogSettings::class)
            //
            // The logging central registry.
            //
            ->share (LogCentralInterface::class)
            ->alias (LogCentral::class, LogCentralInterface::class)
            ->delegate (LogCentralInterface::class, function (LoggingSetupInterface $loggingSetup) {
              $logCentral = new LogCentral;
              $loggingSetup->setup ($logCentral);
              return $logCentral;
            })
            //
            // Define the default logging configurator; it may be overridden later.
            //
            ->alias (LoggingSetupInterface::class, DefaultLoggingSetup::class);
        })
      //
      ->onReconfigure (function (LogCentral $logCentral) {
        $logCentral->setup ();
      });
  }

}
