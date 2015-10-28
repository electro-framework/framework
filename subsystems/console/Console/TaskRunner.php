<?php
namespace Selenia\Console;
use Robo\Config;
use Robo\Result;
use Robo\Runner;
use Robo\TaskInfo;
use Selenia\Application;
use Selenia\Console\TaskRunner\ConsoleIO;
use Selenia\Interfaces\InjectorInterface;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class TaskRunner extends Runner
{
  /**
   * @var ConsoleIO
   */
  protected $io;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var SymfonyConsole
   */
  private $console;
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
  }

  /**
   * Boots a Selenia Application and the runs the specified command.
   * @param array|null $args
   */
  static function run (array $args = null)
  {
    global $application; //TODO: remove this

    $argv = $_SERVER['argv'];
    $application = new Application;
    $application->setup (getcwd ());
    $application->boot ();
    $injector = $application->injector;
    $console  = new SymfonyConsole ('Selenia Task Runner', self::VERSION);
    $io       = new ConsoleIO;
    $args     = $args ?: $argv;

    $injector
      ->share ($io)
      ->share ($console);

    $runner = new static ($io, $application, $console, $injector);
    $runner->setupStandardIO($args);
    $runner->execute ($args);
  }

  function setupStandardIO ($args) {
    // Color support manual override:
    $hasColorSupport = in_array ('--ansi', $args) ? true : (in_array ('--no-ansi', $args) ? false : null);

    $input  = $this->prepareInput ($args);
    $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, $hasColorSupport);
    Config::setOutput ($output);
    $this->io->setInput ($input);
    $this->io->setOutput ($output);
  }

  function execute ($args = null)
  {
    // Setup

    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'handleError']);
    $this->stopOnFail ();
    $this->customizeColors ();

    // Merge tasks from all registered task classes

    foreach ($this->app->taskClasses as $class) {
      if (!class_exists ($class)) {
        $this->getOutput ()->writeln ("<error>Task class '$class' was not found</error>");
        exit(1);
      }
      $this->mergeTasks ($this->console, $class);
    }

    // Run the given command

    $this->console->run ($this->io->getInput(), $this->io->getOutput());
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
      $command->setCode (function (InputInterface $input) use ($roboTasks, $commandName, $passThrough) {
        // get passthru args
        $args = $input->getArguments ();
        array_shift ($args);
        if ($passThrough) {
          $args[key (array_slice ($args, -1, 1, true))] = $passThrough;
        }
        $args[] = $input->getOptions ();

        $res = call_user_func_array ([$roboTasks, $commandName], $args);
        if (is_int ($res)) exit($res);
        if (is_bool ($res)) exit($res ? 0 : 1);
        if ($res instanceof Result) exit($res->getExitCode ());
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
      ->setColor ('warning', new OutputFormatterStyle ('red', 'yellow'));
  }

}
