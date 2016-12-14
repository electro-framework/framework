<?php
namespace Electro\Debugging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\WebProfile;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use PhpKit\WebConsole\Loggers\Specialized\PSR7ResponseLogger;
use Psr\Log\LoggerInterface;

class DebuggingModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel
      ->onRegisterServices (
        function (InjectorInterface $injector) {
          $injector
            ->share (DebugSettings::class);
        });

    $kernel->onConfigure (
      function (LoggerInterface $logger, DebugSettings $settings) {
        if ($settings->webConsole) {
          if ($settings->logRequest)
            DebugConsole::registerPanel ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
          if ($settings->logResponse)
            DebugConsole::registerPanel ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
          if ($settings->logRouting)
            DebugConsole::registerPanel ('routes', new ConsoleLogger ('Routing', 'fa fa-location-arrow'));
          if ($settings->logNavigation)
            DebugConsole::registerPanel ('navigation', new ConsoleLogger ('Navigation', 'fa fa-compass big'));
          if ($settings->logConfig)
            DebugConsole::registerPanel ('config', new ConsoleLogger ('Configuration', 'fa fa-cogs'));
          if ($settings->logSession)
            DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
          if ($settings->logDatabase)
            DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
          if ($settings->logProfiling)
            DebugConsole::registerLogger ('trace', new ConsoleLogger ('Trace', 'fa fa-clock-o big'));
          if ($settings->logView)
            DebugConsole::registerPanel ('view', new ConsoleLogger ('View', 'fa fa-eye'));
          if ($settings->logModel)
            DebugConsole::registerPanel ('model', new ConsoleLogger ('Model', 'fa fa-table'));
          if ($settings->logDOM)
            DebugConsole::registerPanel ('DOM', new ConsoleLogger ('Server-side DOM', 'fa fa-sitemap'));
//    DebugConsole::registerPanel ('exceptions', new ConsoleLogger ('Exceptions', 'fa fa-bug'));

          // Writing to the logger also writes to the Inspector panel.
          if ($logger instanceof Logger)
            $logger->pushHandler (new WebConsoleMonologHandler($settings->debugLevel));
        }
      });
  }

}
