<?php
namespace Selenia;
use Robo\Config;
use Robo\Result;
use Robo\Runner;
use Robo\TaskInfo;
use Selenia\TaskRunner\ConsoleIO;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class TaskRunner extends Runner
{
  /**
   * @var ConsoleIO
   */
  protected $io;

  function __construct ()
  {
    //TODO: inject ConsoleIO service
    $this->io = new ConsoleIO;
  }

  function execute ($args = null)
  {
    global $application;
    $argv = $_SERVER['argv'];

    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'handleError']);

    // Color support manual override:
    $hasColorSupport = in_array ('--ansi', $argv) ? true : (in_array ('--no-ansi', $argv) ? false : null);

    $input = $this->prepareInput ($args ?: $argv);
    $output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, $hasColorSupport);
    Config::setOutput ($output);
    $this->io->setInput ($input);
    $this->io->setOutput ($output);

    $app = $application->console = new ConsoleApplication ('Selenia Task Runner', self::VERSION);

    $this->init ();

    foreach ($application->taskClasses as $class) {
      if (!class_exists ($class)) {
        $this->getOutput ()->writeln ("<error>Task class '$class' was not found</error>");
        exit(1);
      }
      $this->mergeTasks ($app, $class);
    }

    $app->run ($input);
  }

  /**
   * Performs additional initialization after the application is set up, but before any tasks are merged.
   */
  function init ()
  {
    global $application;
    if (!isset($application)) {
      $this->say ("Selenia tasks must be run from the 'selenia' command");
      exit (1);
    }
    $this->stopOnFail ();
    $this->customizeColors ();
  }

  /**
   * Boots an Application and the runs the specified command.
   * @param array|null $args
   */
  function run (array $args = null)
  {
    global $application;
    $application = new Application();
    $application->setup (getcwd ());
    $this->execute ($args);
  }

  /**
   * @param ConsoleApplication $app
   * @param string             $className
   */
  protected function mergeTasks ($app, $className)
  {
    $roboTasks = new $className ($this->io);

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
