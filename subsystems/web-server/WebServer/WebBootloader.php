<?php
namespace Electro\WebServer;

use Dotenv\Dotenv;
use Electro\Interfaces\BootloaderInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Kernel\Config\KernelModule;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Logging\Config\LoggingModule;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\DebugConsole\DebugConsoleSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;

/**
 * Provides the standard bootstrap procedure for web applications.
 *
 * - Sets up all framework services required for HTTP request handling.
 * - Transfers execution to the web-server subsystem.
 */
class WebBootloader implements BootloaderInterface
{
  /**
   * @var bool
   */
  private $debugMode;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  function boot ($rootDir, $urlDepth = 0, callable $onStartUp = null)
  {
    $rootDir = normalizePath ($rootDir);

    // Initialize some settings from environment variables

    if (file_exists ("$rootDir/.env")) {
      $dotenv = new Dotenv ($rootDir);
      $dotenv->load ();
    }

    // Load the kernel's configuration

    /** @var KernelSettings $kernelSettings */
    $kernelSettings = $this->kernelSettings = $this->injector
      ->share (KernelSettings::class, 'app')
      ->make (KernelSettings::class);

    $kernelSettings->isWebBased = true;
    $kernelSettings->setApplicationRoot ($rootDir, $urlDepth);

    // Setup debugging

    $this->setupDebugging ($rootDir);
    // Temporarily set framework path mapping here for errors thrown during modules loading.
    ErrorConsole::setPathsMap ($kernelSettings->getMainPathMap ());

    /*
     * Boot up the framework's core embedded modules.
     *
     * This occurs before the framework's main startup sequence.
     * Unlike the later, which is managed automatically, this pre-startup process is manually defined and consists of
     * just a few core services that must be setup before any other module loads.
     * Note: these modules are special and they do not implement ModuleInterface.
     */
    $this->injector->execute ([LoggingModule::class, 'register']);
    $this->injector->execute ([KernelModule::class, 'register']);

    // Boot up the framework/application's modules.

    /** @var KernelInterface $kernel */
    $kernel = $this->injector->make (KernelInterface::class);

    if ($onStartUp)
      $onStartUp ($kernel);

    // Boot up all modules.
    $kernel->boot ();

    // Finalize.

    if ($this->debugMode)
      $this->setDebugPathsMap ($this->injector->make (ModulesRegistry::class));

    return 0;
  }

  /**
   * Last resort error handler.
   * <p>It is only activated if an error occurs outside of the HTTP handling pipeline.
   *
   * @param \Exception|\Error $e
   */
  function exceptionHandler ($e)
  {
//    if ($this->logger)
//      $this->logger->error ($e->getMessage (),
//        ['stackTrace' => str_replace ("{$this->kernelSettings->baseDirectory}/", '', $e->getTraceAsString ())]);
    DebugConsole::outputContent (true);
  }

  /**
   * Configures path mappings for the ErrorHandler, so that links to files on symlinked directories are converted to
   * links on the main project tree, allowing easier files editing on an IDE.
   *
   * @param ModulesRegistry $registry
   */
  private function setDebugPathsMap (ModulesRegistry $registry)
  {
    $map = $this->kernelSettings->getMainPathMap ();
    $map = array_merge ($map, $registry->getPathMappings ());
    ErrorConsole::setPathsMap ($map);
  }

  /**
   * @param string $rootDir
   */
  private function setupDebugging ($rootDir)
  {
    set_exception_handler ([$this, 'exceptionHandler']);

    $debug = $this->kernelSettings->debugMode = $this->debugMode = getenv ('DEBUG') == 'true';
    $this->injector->defineParam ('debugMode', $debug);

    $debugConsole = getenv ('CONSOLE') == 'true';
    $this->injector->defineParam ('debugConsole', $debugConsole);

    ErrorConsole::init ($debug, $rootDir);
    ErrorConsole::setAppName ($this->kernelSettings->appName);

    $settings                    = new DebugConsoleSettings;
    $settings->defaultPanelTitle = 'Inspector';
    $settings->defaultPanelIcon  = 'fa fa-search';
    DebugConsole::init ($debug, $settings);
  }

}
