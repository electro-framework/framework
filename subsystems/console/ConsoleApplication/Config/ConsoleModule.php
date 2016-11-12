<?php
namespace Electro\ConsoleApplication\Config;

use Electro\ConsoleApplication\ConsoleApplication;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Symfony\Component\Console\Application as SymfonyConsole;

class ConsoleModule implements ModuleInterface
{
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
      ->onRun (function (ConsoleApplication $consoleApp) use ($kernel) {
        // If no code on the startup process has set the console instance's input/output, set it now.
        if (!$consoleApp->getIO ()->getInput ())
          $consoleApp->setupStandardIO ($_SERVER['argv']);

        // Run the framework's console subsystem, which then runs the terminal-based application.
        $kernel->setExitCode ($consoleApp->execute ());
      });
  }

}
