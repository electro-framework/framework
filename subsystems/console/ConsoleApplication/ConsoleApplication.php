<?php
namespace Electro\ConsoleApplication;

use App\Bootloader;
use Electro\ConsoleApplication\Config\ConsoleSettings;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use ReflectionProperty;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ConfigAwareTrait;
use Robo\Config;
use Robo\Result;
use Robo\Robo;
use Robo\Runner;
use Electro\ConsoleApplication\Lib\TaskInfo;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal as SymfonyTerminal;

/**
 * Represents a console-based Electro application.
 */
class ConsoleApplication
{
  use ConfigAwareTrait;

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

        /**
         * @var ContainerInterface
         */
        private $roboContainer;

	/**
	 * ConsoleApplication constructor.
	 *
	 * ><p>**Note:** you'll have to configure the IO channels (ex. calling {@see setupStandardIO}) before running the
	 * application.
	 *
	 * @param ConsoleIO         $io
	 * @param ConsoleSettings   $settings
	 * @param SymfonyConsole    $console
	 * @param InjectorInterface $injector
	 */
	function __construct(ConsoleIO $io, ConsoleSettings $settings, SymfonyConsole $console, SymfonyTerminal $terminal, InjectorInterface $injector)
	{
          $this->io = $io;
          $this->console = $console;
          $this->injector = $injector;
          $this->settings = $settings;
          $console->setAutoExit(false);
          $io->terminalSize([$terminal->getWidth(), $terminal->getHeight()]);

          $config = new Config\Config();
          $container = Robo::createContainer($this->console, $config);
          // $container->add('logger', function () use ($injector) {
          //   return $injector->make(LoggerInterface::class);
          // });
          Robo::finalizeContainer($container);
          $this->roboContainer = $container;
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
	function executeOverride($input = null)
	{
		$this->stopOnFail();
		$this->customizeColors();

		// Merge tasks from all registered task classes

		foreach ($this->settings->getTaskClasses() as $class)
		{
			if (!class_exists($class))
			{
				$this->getOutput()->writeln("<error>Task class '$class' was not found</error>");
				exit(1);
			}
			$this->mergeTasks($this->console, $class);
		}

		// Run the given command

		return $this->console->run($input ?: $this->io->getInput(), $this->io->getOutput());
	}

	/**
	 * Returns the console application's underlying console instance.
	 *
	 * @return SymfonyConsole
	 */
	function getConsole()
	{
		return $this->console;
	}

	/**
	 * Returns the console application's input/output interface.
	 *
	 * @return ConsoleIOInterface
	 */
	function getIO()
	{
		return $this->io;
	}

	/**
	 * Runs the specified console command, with the given arguments, as if it was invoked from the command line.
	 *
	 * @param string   $name Command name.
	 * @param string[] $args Command arguments.
	 * @return int 0 if everything went fine, or an error code
	 * @throws Exception
	 */
	function runOverride($name, array $args = [])
	{
		$args = array_merge(['', $name], $args);
		return $this->executeOverride(new ArgvInput($args));
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
	 * @throws Exception
	 */
	function runAndCapture($name, array $args = [], &$outStr = null, OutputInterface $output = null, $decorated = true, $verbosity = OutputInterface::VERBOSITY_NORMAL)
	{
		$args = array_merge(['', $name], $args);
		if ($output)
		{
			$verbosity = $output->getVerbosity();
			$decorated = $output->isDecorated();
		}
		$out = new BufferedOutput($verbosity, $decorated);
		$r = $this->console->run(new ArgvInput($args), $out);
		$outStr = $out->fetch();
		return $r;
	}

	/**
	 * Sets up the ConsoleApplication instance to runs the specified console command from within a Composer execution
	 * context.
	 *
	 * @see Bootloader
	 *
	 * @param string                       $name Command name.
	 * @param string[]                     $args Command arguments.
	 * @param Composer\Script\PackageEvent $event
	 */
	public function runCommand($name, $args = [], $event = null)
	{
		$output = null;
		if ($event)
		{
			$io = $event->getIO();

			// Check for the presence of the -q|--quiet option.
			$r = new ReflectionProperty($io, 'input');
			$r->setAccessible(true);
			/** @var ArgvInput $input */
			$input = $r->getValue($io);
			if ($input->getOption('quiet'))
				$output = new NullOutput;

			else
			{
				// DO NOT change the order of evaluation!
				switch (true)
				{
					case $io->isDebug():
						$verbose = ConsoleOutput::VERBOSITY_DEBUG;
						break;
					case $io->isVeryVerbose():
						$verbose = ConsoleOutput::VERBOSITY_VERY_VERBOSE;
						break;
					case $io->isVerbose():
						$verbose = ConsoleOutput::VERBOSITY_VERBOSE;
						break;
					default:
						$verbose = ConsoleOutput::VERBOSITY_NORMAL;
				}
				$output = new ConsoleOutput($verbose, $io->isDecorated());
			}
		}
		$this->setupStandardIO(array_merge(['', $name], $args), $output);
	}

	/**
	 * Creates the default console I/O channels.
	 *
	 * @param string[]        $args   The command-line arguments.
	 * @param OutputInterface $output [optional] use this output interface, otherwise create a new one.
	 */
	function setupStandardIO($args, OutputInterface $output = null)
	{
		$input = new ArgvInput($args);
		if (!$output)
		{
			// Color support manual override:
			$hasColorSupport = in_array('--ansi', $args) ? true : (in_array('--no-ansi', $args) ? false : null);
			$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_NORMAL, $hasColorSupport);
		}
		$this->io->setInput($input);
		$this->io->setOutput($output);
		// Robo Command classes can access the current output via Config::get('output')
		//Config::setOutput ($output);
	}

	/**
	 * @param SymfonyConsole $app
	 * @param string         $className
	 */
	protected function mergeTasks($app, $className)
	{
		$commandNames = array_filter(get_class_methods($className),
			function ($m) use ($className)
			{
				$method = new ReflectionMethod($className, $m);
				return !in_array($m, ['__construct']) && !$method->isStatic(); // Reject constructors and static methods.
			});

		//$passThrough = $this->passThroughArgs;
		$passThrough = null;

		foreach ($commandNames as $commandName)
		{
			$command = $this->createCommand(new TaskInfo($className, $commandName));
			$command->setCode(function (InputInterface $input, OutputInterface $output)
			use ($className, $commandName, $passThrough)
			{
				// get passthru args
				$args = $input->getArguments();
				array_shift($args);
				if ($passThrough)
				{
					$args[key(array_slice($args, -1, 1, true))] = $passThrough;
				}
				$args[] = $input->getOptions();

				// Robo Command classes can access the current output via Config::get('output')
				// This output may have been customized for a specific command, usually when being run internally with
				// output capture.
				//Config::setOutput($output);// Call to undefined method Robo\Config::setOutput()

				$roboTasks = $this->injector->make($className);
                                $builder = new CollectionBuilder($roboTasks);
                                $builder->setConfig (Robo::config());
                                $builder->setLogger ($this->injector->make ('logger'));
                                $roboTasks->setBuilder ($builder);
                                $roboTasks->setContainer($this->roboContainer);
				$res = call_user_func_array([$roboTasks, $commandName], array_values($args)); // Cannot use positional argument after named argument

                                // Restore the setting to the main output stream.
				//Config::setOutput($this->io->getOutput());

				if (is_int($res))
					return $res;
				if (is_bool($res))
					return $res ? 0 : 1;
				if ($res instanceof Result)
					return $res->getExitCode();
				return $res;
			});
			$app->add($command);
		}
	}

	protected function stopOnFail($stopOnFail = true)
	{
		Result::$stopOnFail = $stopOnFail;
	}

	private function customizeColors()
	{
		$this->io
			->setColor('title', new OutputFormatterStyle('magenta'))
			->setColor('question', new OutputFormatterStyle('cyan'))
			->setColor('red', new OutputFormatterStyle('red'))
			->setColor('warning', new OutputFormatterStyle('black', 'yellow'))
			->setColor('error-info', new OutputFormatterStyle('yellow', 'red'))
			->setColor('kbd', new OutputFormatterStyle('green'));
	}

	public function createCommand(TaskInfo $taskInfo)
	{
		$task = new Command($taskInfo->getName());
		if ($desc = $taskInfo->getDescription())
			$task->setDescription($desc);
		if ($help = $taskInfo->getHelp())
			$task->setHelp($help);

		$args = $taskInfo->getArguments();
		foreach ($args as $name => $val)
		{
			$description = $taskInfo->getArgumentDescription($name);
			if ($val === TaskInfo::PARAM_IS_REQUIRED)
			{
				$task->addArgument($name, InputArgument::REQUIRED, $description);
			}
			elseif (is_array($val))
			{
				$task->addArgument($name, InputArgument::IS_ARRAY, $description, $val);
			}
			else
			{
				$task->addArgument($name, InputArgument::OPTIONAL, $description, $val);
			}
		}
		$opts = $taskInfo->getOptions();
		foreach ($opts as $name => $val)
		{
			$description = $taskInfo->getOptionDescription($name);

			$fullname = $name;
			$shortcut = '';
			if (strpos($name, '|'))
			{
				[$fullname, $shortcut] = explode('|', $name, 2);
			}

			if (is_bool($val))
			{
				$task->addOption($fullname, $shortcut, InputOption::VALUE_NONE, $description);
			}
			else
			{
				$task->addOption($fullname, $shortcut, InputOption::VALUE_OPTIONAL, $description, $val);
			}
		}

		return $task;
	}

}
