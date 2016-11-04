<?php
namespace Electro\ConsoleApplication;

use Dotenv\Dotenv;
use Electro\ConsoleApplication\Config\ConsoleSettings;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\BootloaderInterface;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Kernel\Config\KernelModule;
use Electro\Kernel\Config\KernelSettings;
use Electro\Logging\Config\LoggingModule;
use Symfony\Component\Console\Application as SymfonyConsole;

class ConsoleBootloader implements BootloaderInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * @internal
   * @param mixed $arg
   * @return string
   */
  static public function formatErrorArg ($arg)
  {
    if (is_object ($arg))
      switch (get_class ($arg)) {
        case \ReflectionMethod::class:
          /** @var \ReflectionMethod $arg */
          return $arg->getDeclaringClass ()->getName () . '::' . $arg->getName ();
        case \ReflectionFunction::class:
          /** @var \ReflectionFunction $arg */
          return sprintf ('Closure at %s line %d', $arg->getFileName (), $arg->getStartLine ());
        case \ReflectionParameter::class:
          /** @var \ReflectionParameter $arg */
          return '$' . $arg->getName ();
        default:
          return typeOf ($arg);
      }
    if (is_array ($arg))
      return sprintf ('[%s]', implode (',', map ($arg, [__CLASS__, 'formatErrorArg'])));
    return str_replace ('\\\\', '\\', var_export ($arg, true));
  }

  function boot ($rootDir, $urlDepth = 0, callable $onStartUp = null)
  {
    // Setup error handling

    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'errorHandler']);
    set_exception_handler ([$this, 'exceptionHandler']);

    // Initialize some settings from environment variables

    $rootDir = normalizePath ($rootDir);

    if (file_exists ("$rootDir/.env")) {
      $dotenv = new Dotenv ($rootDir);
      $dotenv->load ();
    }

    // Load the kernel's configuration

    /** @var KernelSettings $kernelSettings */
    $kernelSettings = $this->injector
      ->share (KernelSettings::class, 'app')
      ->make (KernelSettings::class);

    $kernelSettings->isConsoleBased = true;
    $kernelSettings->setRootDir ($rootDir);

    // Setup the console.

    $console  = new SymfonyConsole ("\nWorkman Task Runner");
    $io       = new ConsoleIO;
    $settings = new ConsoleSettings;

    // Setup debugging

    $this->injector->defineParam ('debugMode', false)
                   ->defineParam ('debugConsole', false);

    // Share some services

    $this->injector->alias (ConsoleIOInterface::class, ConsoleIO::class)
                   ->share ($io)
                   ->share ($console)
                   ->share ($settings);

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

    // Create a new application instance.
    // This must be done before the kernel boots up, so that modules may access the instance.

    $consoleApp = new ConsoleApplication ($io, $settings, $console, $this->injector);
    $this->injector->share ($consoleApp);

    // Boot up the framework/application's modules.

    /** @var KernelInterface $kernel */
    $kernel = $this->injector->make (KernelInterface::class);

    if ($onStartUp)
      $onStartUp ($kernel);

    // Boot up all modules.
    $kernel->boot ();

    // If no code on the startup process has set the console instance's input/output, set it now.
    if (!$consoleApp->getIO ()->getInput ())
      $consoleApp->setupStandardIO ($_SERVER['argv']);

    // Run the framework's console subsystem, which then runs the terminal-based application.
    return $consoleApp->execute ();
  }

  /**
   * Converts PHP < 7 errors to ErrorExceptions.
   *
   * @internal
   * @param int    $code
   * @param string $msg
   * @param string $file
   * @param int    $line
   * @return bool
   * @throws \ErrorException
   */
  public function errorHandler ($code = null, $msg = null, $file, $line)
  {
    if (error_reporting () === 0)
      return true;
    throw new \ErrorException ($msg, $code, 1, $file, $line);
  }

  /**
   * Outputs the full stack trace with enhanced information.
   *
   * @internal
   * @param \Exception|\Throwable $exception
   */
  function exceptionHandler ($exception)
  {
    $NL    = PHP_EOL;
    $stack = $exception->getTrace ();
    if ($exception instanceof \ErrorException)
      array_shift ($stack);
    $c = count ($stack);
    echo sprintf ("{$NL}Unhandled exception: %s$NL{$NL}Stack trace:$NL$NL%4d. Throw %s$NL      from %s, line %d$NL$NL",
      color ('red', $exception->getMessage ()),
      $c + 1,
      color ('yellow', get_class ($exception)),
      $exception->getFile (),
      $exception->getLine ()
    );
    foreach ($stack as $i => $l)
      echo sprintf ("%4d. Call %s$NL      from %s, line %d$NL$NL",
        $c - $i,
        color ('yellow', sprintf ('%s%s (%s)',
          isset($l['class']) ? $l['class'] . get ($l, 'type', '::') : '',
          $l['function'],
          implode (',', map ($l['args'], [__CLASS__, 'formatErrorArg']))
        )),
        get ($l, 'file', 'an unknown location'),
        get ($l, 'line')
      );
  }

  /**
   * Displays errors not catched during the app's execution.
   */
  public function shutdown ()
  {
    $error = error_get_last ();
    if (!is_array ($error)) return;
    echo sprintf ("ERROR: %s \nin %s:%d\n", color ('red', $error['message']), $error['file'], $error['line']);
  }

}
