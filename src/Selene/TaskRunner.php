<?php
namespace Selene;
use Robo\Config;
use Robo\Result;
use Robo\Runner;
use Robo\TaskInfo;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class TaskRunner extends Runner
{
  function execute ($input = null)
  {
    global $application;
    $argv = $_SERVER['argv'];

    register_shutdown_function ([$this, 'shutdown']);
    set_error_handler ([$this, 'handleError']);

    // Color support manual override:
    $hasColorSupport = in_array ('--ansi', $argv) ? true : (in_array ('--no-ansi', $argv) ? false : null);

    Config::setOutput (new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, $hasColorSupport));
    $input = $this->prepareInput ($input ?: $argv);

    $app                        = $application->console = new ConsoleApplication ('Selene Task Runner', self::VERSION);
    $application->taskClasses[] = $application->tasksClass;

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
    $roboTasks = new $className;

    $commandNames = array_filter (get_class_methods ($className), function ($m) {
      return !in_array ($m, ['__construct']);
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

}
