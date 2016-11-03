<?php
namespace Electro\Debugging\Config;

use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use PhpKit\WebConsole\Loggers\Specialized\PSR7ResponseLogger;
use Psr\Log\LoggerInterface;

class DebuggingModule implements ModuleInterface
{
  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onConfigure (
      function (LoggerInterface $logger, $debugConsole) {
        if ($debugConsole) {
          DebugConsole::registerPanel ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
          DebugConsole::registerPanel ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
          DebugConsole::registerPanel ('routes', new ConsoleLogger ('Routing', 'fa fa-location-arrow'));
          DebugConsole::registerPanel ('navigation', new ConsoleLogger ('Navigation', 'fa fa-compass big'));
          DebugConsole::registerPanel ('config', new ConsoleLogger ('Configuration', 'fa fa-cogs'));
          DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
          DebugConsole::registerPanel ('DOM', new ConsoleLogger ('Server-side DOM', 'fa fa-sitemap'));
          DebugConsole::registerPanel ('view', new ConsoleLogger ('View', 'fa fa-eye'));
          DebugConsole::registerPanel ('model', new ConsoleLogger ('Model', 'fa fa-table'));
          DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
          DebugConsole::registerLogger ('trace', new ConsoleLogger ('Trace', 'fa fa-clock-o big'));
//    DebugConsole::registerPanel ('exceptions', new ConsoleLogger ('Exceptions', 'fa fa-bug'));

          // Writing to the logger also writes to the Inspector panel.
          if ($logger instanceof Logger)
            $logger->pushHandler (new WebConsoleMonologHandler(getenv ('DEBUG_LEVEL') ?: Logger::DEBUG));
        }
      });
  }

}
