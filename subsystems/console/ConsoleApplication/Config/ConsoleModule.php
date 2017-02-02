<?php

namespace Electro\ConsoleApplication\Config;

use Electro\ConsoleApplication\ConsoleApplication;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Logging\Config\LogSettings;
use Electro\Logging\Lib\ConsoleLogger;
use Electro\Profiles\ConsoleProfile;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application as SymfonyConsole;

class ConsoleModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [ConsoleProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel
      ->onRegisterServices (
        function (InjectorInterface $injector) {
          $injector
            ->share (ConsoleSettings::class)
            //
            ->alias (ConsoleIOInterface::class, ConsoleIO::class)
            ->share (ConsoleIO::class)
            //
            ->delegate (SymfonyConsole::class, function () {
              return new SymfonyConsole ("\nWorkman Task Runner");
            })
            ->share (SymfonyConsole::class)
            //
            ->share (ConsoleApplication::class);
        })
      //
      ->onRun (function (ConsoleApplication $consoleApp, Logger $mainLogger, LogSettings $logSettings) use ($kernel) {

        // If no code on the startup process has set the console instance's input/output, set it now.
        if (!$consoleApp->getIO ()->getInput ())
          $consoleApp->setupStandardIO ($_SERVER['argv']);

        // Configure the main logger.
        $log = new ConsoleLogger ($consoleApp->getIO ()->getOutput ());
        $log->setLogFormat ($logSettings->consoleLogFormat);
        $log->setDateTimeFormat ($logSettings->dateTimeFormat);
        $mainLogger->pushHandler (new PsrHandler($log));

        $kernel->setExitCode ($consoleApp->execute ());
      });
  }

}
