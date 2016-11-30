<?php
namespace Electro\ConsoleApplication;

use Electro\Configuration\Lib\DotEnv;
use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\BootloaderInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Kernel\Config\KernelModule;
use Electro\Kernel\Config\KernelSettings;

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
          return sprintf ('ReflectionMethod<%s::$s>', $arg->getDeclaringClass ()->getName (), $arg->getName ());
        case \ReflectionFunction::class:
          /** @var \ReflectionFunction $arg */
          return sprintf ('ReflectionFunction<function at %s line %d>', $arg->getFileName (), $arg->getStartLine ());
        case \ReflectionParameter::class:
          /** @var \ReflectionParameter $arg */
          return sprintf ('ReflectionParameter<$%s>', $arg->getName ());
        default:
          return typeOf ($arg);
      }
    if (is_array ($arg))
      return sprintf ('[%s]', implode (',', map ($arg, [__CLASS__, 'formatErrorArg'])));
    return str_replace ('\\\\', '\\', var_export ($arg, true));
  }

  function boot ($rootDir, $urlDepth = 0, callable $onStartUp = null)
  {
    $rootDir = normalizePath ($rootDir);

    // Initialize some settings from environment variables

    $dotenv = new Dotenv ("$rootDir/.env");
    try {
      $dotenv->load ();
    }
    catch (ConfigException $e) {
      echo $e->getMessage();
      return 1;
    }

    // Load the kernel's configuration

    /** @var KernelSettings $kernelSettings */
    $kernelSettings = $this->injector
      ->share (KernelSettings::class, 'app')
      ->make (KernelSettings::class);

    $kernelSettings->isConsoleBased = true;
    $kernelSettings->setApplicationRoot ($rootDir, $urlDepth);

    // Setup debugging (must be done before instantiating the kernel, but after instantiating its settings).

    $this->setupDebugging ($rootDir);

    // Boot up the framework's kernel.

    $this->injector->execute ([KernelModule::class, 'register']);

    // Boot up the framework/application's modules.

    /** @var KernelInterface $kernel */
    $kernel = $this->injector->make (KernelInterface::class);

    if ($onStartUp)
      $onStartUp ($kernel);

    // Boot up all modules.
    $kernel->boot ();

    return $kernel->getExitCode ();
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

  /**
   * @param string $rootDir
   */
  private function setupDebugging ($rootDir)
  {
    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'errorHandler']);
    set_exception_handler ([$this, 'exceptionHandler']);

    $this->injector->defineParam ('devEnv', false);
    $this->injector->defineParam ('webConsole', false);
  }

}
