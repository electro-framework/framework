<?php
namespace Selenia\Debugging\Config;

use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use PhpKit\WebConsole\Loggers\Specialized\PSR7ResponseLogger;
use Psr\Log\LoggerInterface;
use Selenia\Interfaces\ModuleInterface;

class DebuggingModule implements ModuleInterface
{
  /**
   * @var LoggerInterface
   */
  private $logger;

  function configure (LoggerInterface $logger)
  {
    DebugConsole::registerPanel ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
    DebugConsole::registerPanel ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
    DebugConsole::registerPanel ('routes', new ConsoleLogger ('Routing', 'fa fa-location-arrow'));
    DebugConsole::registerPanel ('navigation', new ConsoleLogger ('Navigation', 'fa fa-compass big'));
    DebugConsole::registerPanel ('config', new ConsoleLogger ('Configuration', 'fa fa-cogs'));
    DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
    DebugConsole::registerPanel ('DOM', new ConsoleLogger ('Server-side DOM', 'fa fa-sitemap'));
    DebugConsole::registerPanel ('vm', new ConsoleLogger ('View Model', 'fa fa-table'));
    DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
    DebugConsole::registerLogger ('trace', new ConsoleLogger ('Trace', 'fa fa-clock-o big'));
//    DebugConsole::registerPanel ('exceptions', new ConsoleLogger ('Exceptions', 'fa fa-bug'));

    // Writing to the logger also writes to the Inspector panel.
    $this->logger = $logger;
    if ($this->logger instanceof Logger)
      $this->logger->pushHandler (new WebConsoleMonologHandler(getenv ('DEBUG_LEVEL') ?: Logger::DEBUG));
  }

}
