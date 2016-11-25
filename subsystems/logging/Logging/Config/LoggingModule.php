<?php
namespace Electro\Logging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * This subsystem provides a main logger for the application.
 *
 * <p>To add logging handlers to the main logger, inject a `LoggerInterface` instance into your class and call
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
          ->delegate (LoggerInterface::class, function (KernelSettings $kernelSettings) {
            $logger = new Logger ('main');
            if ($kernelSettings->isConsoleBased)
              $logger->pushHandler (new StreamHandler('php://stderr', getenv ('DEBUG_LEVEL') ?: Logger::DEBUG));
            return $logger;
          });
      });
  }

}
