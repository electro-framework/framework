<?php
namespace Selenia\Core\ConsoleApplication;

use Robo\Config;
use Robo\Result;
use Robo\Runner;
use Robo\TaskInfo;
use Selenia\Application;
use Selenia\Core\Assembly\Services\ModulesLoader;
use Selenia\Core\ConsoleApplication\Services\ConsoleIO;
use Selenia\Core\DependencyInjection\ServiceContainer;
use Selenia\Core\DependencyInjection\ServiceContainerInterface;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Interfaces\InjectorInterface;
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
   * @var Application
   */
  private $app;
  /**
   * @var InjectorInterface
   */
  private $injector;

  function __construct (ConsoleIO $io, Application $app, SymfonyConsole $console, InjectorInterface $injector)
  {
    $this->io       = $io;
    $this->app      = $app;
    $this->console  = $console;
    $this->injector = $injector;
    $console->setAutoExit (false);
    $io->terminalSize ($console->getTerminalDimensions ());
  }

  /**
   * A factory for creating an instance of a console-based Selenia application.
   *
   * <p>Boots a Selenia Application and creates a console command runner with a base configuration.
   * > You'll have to configure the IO channels (ex. calling `setupStandardIO()` on the runner) before running the
   * application.
   *
   * @param InjectorInterface $injector Provide your favorite dependency injector.
   * @return static
   */
  static function make (InjectorInterface $injector)
  {
    // Create and register the foundational framework services.

    $container = new ServiceContainer($injector);
    $injector
      ->share ($injector)
      ->alias (InjectorInterface::class, get_class ($injector))
      ->share ($container)
      ->alias (ServiceContainerInterface::class, ServiceContainer::class);

    /** @var Application $app */
    $app = $injector
      ->share (Application::class)
      ->make (Application::class);
    $container->app = $app;

    $app->isConsoleBased = true;
    $app->setup (getcwd ());
    $app->preboot ();

    // Setup debugging

//    ErrorHandler::init ();
//    DebugConsole::init ($app->debugMode);
//    $injector->defineParam ('debugMode', $app->debugMode);
    $injector->defineParam ('debugMode', false);

    // Setup the console.

    $console = new SymfonyConsole ('Selenia Console');
    $io      = new ConsoleIO;

    $consoleApp = new static ($io, $app, $console, $injector);

    $injector
      ->alias (ConsoleIOInterface::class, ConsoleIO::class)
      ->share ($io)
      ->share ($console)
      ->share ($consoleApp);

    // Return the initialized application.

    return $consoleApp;
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
    set_error_handler ([$this, 'handleError']);
    $this->stopOnFail ();
    $this->customizeColors ();

    // Bootstrap the application's modules.

    /** @var ModulesLoader $modulesApi */
    $loader = $this->injector->make (ModulesLoader::class);
    $loader->bootModules ();

    // Merge tasks from all registered task classes

    foreach ($this->app->taskClasses as $class) {
      if (!class_exists ($class)) {
        $this->getOutput ()->writeln ("<error>Task class '$class' was not found</error>");
        exit(1);
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
