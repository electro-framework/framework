<?php
namespace Electro\Core\ConsoleApplication;

use Electro\Application;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\ConsoleApplication\Config\ConsoleSettings;
use Electro\Core\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ProfileInterface;
use Robo\Config;
use Robo\Result;
use Robo\Runner;
use Robo\TaskInfo;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplication extends Runner
{
  /**
   * @var SymfonyConsole
   */
  public $console;
  /**
   * @var ConsoleIO
   */
  protected $io;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var ConsoleSettings
   */
  private $settings;

  function __construct (ConsoleIO $io, ConsoleSettings $settings, SymfonyConsole $console, InjectorInterface $injector)
  {
    $this->io       = $io;
    $this->console  = $console;
    $this->injector = $injector;
    $this->settings = $settings;
    $console->setAutoExit (false);
    $io->terminalSize ($console->getTerminalDimensions ());
  }

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

  /**
   * A factory for creating an instance of a console-based Electro application.
   *
   * <p>Boots a Electro Application and creates a console command runner with a base configuration.
   * > You'll have to configure the IO channels (ex. calling `setupStandardIO()` on the runner) before running the
   * application.
   *
   * @param InjectorInterface $injector     Provide your favorite dependency injector.
   * @param string            $profileClass The configuration profile's fully qualified class name.
   * @return static
   */
  static function make (InjectorInterface $injector, $profileClass)
  {
    assert (is_string ($profileClass), '$profileClass must be string');

    // Create and register the foundational framework services.

    $injector
      ->share ($injector)
      ->alias (InjectorInterface::class, get_class ($injector))
      ->alias (ProfileInterface::class, $profileClass);

    /** @var Application $app */
    $app = $injector
      ->share (Application::class, 'app')
      ->make (Application::class);

    $app->isConsoleBased = true;
    $rootDir             = normalizePath (getcwd ());
    $app->setup ($rootDir);

    // Setup debugging

    $injector->defineParam ('debugMode', false);
    $injector->defineParam ('debugConsole', false);

    // Setup the console.

    $console  = new SymfonyConsole ('Workman Task Runner');
    $io       = new ConsoleIO;
    $settings = new ConsoleSettings;

    $injector
      ->alias (ConsoleIOInterface::class, ConsoleIO::class)
      ->share ($io)
      ->share ($console)
      ->share ($settings);

    // Return a new application instance.

    $consoleApp = new static ($io, $settings, $console, $injector);
    $injector->share ($consoleApp);
    return $consoleApp;
  }

  public function errorHandler ($code = null, $msg = null, $file, $line)
  {
    if (error_reporting () === 0)
      return true;
    throw new \ErrorException ($msg, $code, 1, $file, $line);
  }

  /**
   * Outputs the full stack trace with enhanced information.
   *
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
        $l['file'],
        $l['line']
      );
  }

  /**
   * Runs the console.
   *
   * <p>This should be called **ONLY ONCE** per console application instance.
   * <p>Use {@see run()} to run additional commands on the same console instance.
   *
   * @param InputInterface|null $input Overrides the input, if specified.
   * @return int 0 if everything went fine, or an error code
   */
  function execute ($input = null)
  {
    // Setup

    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'errorHandler']);
    set_exception_handler ([$this, 'exceptionHandler']);
    $this->stopOnFail ();
    $this->customizeColors ();

    /** @var Bootstrapper $boot */
    $bootstrapper = $this->injector->make (Bootstrapper::class);
    $bootstrapper->run ();

    // Merge tasks from all registered task classes

    foreach ($this->settings->getTaskClasses () as $class) {
      if (!class_exists ($class)) {
        $this->getOutput ()->writeln ("<error>Task class '$class' was not found</error>");
        exit (1);
      }
      $this->mergeTasks ($this->console, $class);
    }

    // Run the given command

    return $this->console->run ($input ?: $this->io->getInput (), $this->io->getOutput ());
  }

  /**
   * Returns the console application's underlying console instance.
   *
   * @return SymfonyConsole
   */
  function getConsole ()
  {
    return $this->console;
  }

  /**
   * Returns the console application's input/output interface.
   *
   * @return ConsoleIOInterface
   */
  function getIO ()
  {
    return $this->io;
  }

  /**
   * Runs the specified console command, with the given arguments, as if it was invoked from the command line.
   *
   * @param string   $name Command name.
   * @param string[] $args Command arguments.
   * @return int 0 if everything went fine, or an error code
   * @throws \Exception
   */
  function run ($name, array $args = [])
  {
    $args  = array_merge (['', $name], $args);
    $input = $this->prepareInput ($args);
    return $this->execute ($input);
  }

  /**
   * Runs the specified console command, with the given arguments, as if it was invoked from the command line
   * and captures its output.
   *
   * @param string          $name      Command name.
   * @param string[]        $args      Command arguments.
   * @param string          $outStr    Captures the command's output.
   * @param OutputInterface $output    [optional] If set, the output configuration will be copied from it.
   * @param bool            $decorated Set to false to disable colorized output.
   * @param int             $verbosity One of the OutputInterface::VERBOSITY constants.
   * @return int 0 if everything went fine, or an error code
   * @throws \Exception
   */
  function runAndCapture ($name, array $args = [], &$outStr, OutputInterface $output = null, $decorated = true,
                          $verbosity = OutputInterface::VERBOSITY_NORMAL)
  {
    $args = array_merge (['', $name], $args);
    if ($output) {
      $verbosity = $output->getVerbosity ();
      $decorated = $output->isDecorated ();
    }
    $out    = new BufferedOutput ($verbosity, $decorated);
    $r      = $this->console->run ($this->prepareInput ($args), $out);
    $outStr = $out->fetch ();
    return $r;
  }

  /**
   * Creates the default console I/O channels.
   *
   * @param string[]        $args   The command-line arguments.
   * @param OutputInterface $output [optional] use this output interface, otherwise create a new one.
   */
  function setupStandardIO ($args, OutputInterface $output = null)
  {
    $input = $this->prepareInput ($args);
    if (!$output) {
      // Color support manual override:
      $hasColorSupport = in_array ('--ansi', $args) ? true : (in_array ('--no-ansi', $args) ? false : null);
      $output          = new ConsoleOutput (ConsoleOutput::VERBOSITY_NORMAL, $hasColorSupport);
    }
    $this->io->setInput ($input);
    $this->io->setOutput ($output);
    // Robo Command classes can access the current output via Config::get('output')
    Config::setOutput ($output);
  }

  /**
   * @param SymfonyConsole $app
   * @param string         $className
   */
  protected function mergeTasks ($app, $className)
  {
    $roboTasks = $this->injector->make ($className);

    $commandNames = array_filter (get_class_methods ($className),
      function ($m) use ($className) {
        $method = new \ReflectionMethod($className, $m);
        return !in_array ($m, ['__construct']) && !$method->isStatic (); // Reject constructors and static methods.
      });

    $passThrough = $this->passThroughArgs;

    foreach ($commandNames as $commandName) {
      $command = $this->createCommand (new TaskInfo($className, $commandName));
      $command->setCode (function (InputInterface $input, OutputInterface $output)
      use ($roboTasks, $commandName, $passThrough) {
        // get passthru args
        $args = $input->getArguments ();
        array_shift ($args);
        if ($passThrough) {
          $args[key (array_slice ($args, -1, 1, true))] = $passThrough;
        }
        $args[] = $input->getOptions ();

        // Robo Command classes can access the current output via Config::get('output')
        // This output may have been customized for a specific command, usually when being run internally with
        // output capture.
        Config::setOutput ($output);

        $res = call_user_func_array ([$roboTasks, $commandName], $args);
        // Restore the setting to the main output stream.
        Config::setOutput ($this->io->getOutput ());
        if (is_int ($res)) return $res;
        if (is_bool ($res)) return $res ? 0 : 1;
        if ($res instanceof Result) return $res->getExitCode ();
        return $res;
      });
      $app->add ($command);
    }
  }

  protected function stopOnFail ($stopOnFail = true)
  {
    Result::$stopOnFail = $stopOnFail;
  }

  private function customizeColors ()
  {
    $this->io
      ->setColor ('title', new OutputFormatterStyle ('magenta'))
      ->setColor ('question', new OutputFormatterStyle ('cyan'))
      ->setColor ('red', new OutputFormatterStyle ('red'))
      ->setColor ('warning', new OutputFormatterStyle ('black', 'yellow'))
      ->setColor ('error-info', new OutputFormatterStyle ('green', 'red'))
      ->setColor ('kbd', new OutputFormatterStyle ('green'));
  }

}
