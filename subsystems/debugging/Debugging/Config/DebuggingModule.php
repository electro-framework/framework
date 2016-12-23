<?php
namespace Electro\Debugging\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\WebProfile;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\DebugConsole\DebugConsoleSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use PhpKit\WebConsole\Loggers\Specialized\PSR7ResponseLogger;
use Psr\Log\LoggerInterface;

class DebuggingModule implements ModuleInterface
{
  /**
   * Last resort error handler.
   * <p>It is only activated if an error occurs outside of the HTTP handling pipeline.
   *
   * @param \Exception|\Error $e
   */
  static function exceptionHandler ($e)
  {
//    if ($this->logger)
//      $this->logger->error ($e->getMessage (),
//        ['stackTrace' => str_replace ("{$this->kernelSettings->baseDirectory}/", '', $e->getTraceAsString ())]);
  }

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
      function (LoggerInterface $logger, DebugSettings $debugSettings, KernelSettings $kernelSettings) {

        ErrorConsole::init ($debugSettings->devEnv, $kernelSettings->baseDirectory);
        ErrorConsole::setAppName ($kernelSettings->appName);
        // Note: the editorUrl can't be set yet. See: WebServer.

        $settings                    = new DebugConsoleSettings;
        $settings->defaultPanelTitle = 'Inspector';
        $settings->defaultPanelIcon  = 'fa fa-search';
        DebugConsole::init ($debugSettings->webConsole, $settings, $debugSettings->logInspections);

        // Temporarily set framework path mapping here for errors thrown during modules loading.
        ErrorConsole::setPathsMap ($kernelSettings->getMainPathMap ());

//        set_exception_handler ([__CLASS__, 'exceptionHandler']);

        if ($debugSettings->webConsole) {
          if ($debugSettings->logRequest)
            DebugConsole::registerPanel ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
          if ($debugSettings->logResponse)
            DebugConsole::registerPanel ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
          if ($debugSettings->logRouting)
            DebugConsole::registerPanel ('routes', new ConsoleLogger ('Routing', 'fa fa-location-arrow'));
          if ($debugSettings->logNavigation)
            DebugConsole::registerPanel ('navigation', new ConsoleLogger ('Navigation', 'fa fa-compass big'));
          if ($debugSettings->logConfig)
            DebugConsole::registerPanel ('config', new ConsoleLogger ('Configuration', 'fa fa-cogs'));
          if ($debugSettings->logSession)
            DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
          if ($debugSettings->logDatabase)
            DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
          if ($debugSettings->logProfiling)
            DebugConsole::registerLogger ('trace', new ConsoleLogger ('Trace', 'fa fa-clock-o big'));
          if ($debugSettings->logView)
            DebugConsole::registerPanel ('view', new ConsoleLogger ('View', 'fa fa-eye'));
          if ($debugSettings->logModel)
            DebugConsole::registerPanel ('model', new ConsoleLogger ('Model', 'fa fa-table'));
          if ($debugSettings->logDOM)
            DebugConsole::registerPanel ('DOM', new ConsoleLogger ('Server-side DOM', 'fa fa-sitemap'));
//    DebugConsole::registerPanel ('exceptions', new ConsoleLogger ('Exceptions', 'fa fa-bug'));

          // Writing to the logger also writes to the Inspector panel.
          if ($logger instanceof Logger)
            $logger->pushHandler (new WebConsoleMonologHandler($debugSettings->debugLevel));
        }
      });
  }

}
