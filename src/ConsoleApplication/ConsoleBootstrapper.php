<?php
namespace Electro\ConsoleApplication;

use Dotenv\Dotenv;
use Electro\ConsoleApplication\Config\ConsoleSettings;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Interfaces\BootstrapperInterface;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ProfileInterface;
use Electro\Kernel\Config\KernelSettings;
use Symfony\Component\Console\Application as SymfonyConsole;

class ConsoleBootstrapper implements BootstrapperInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  /**
   * @param InjectorInterface $injector     Provide your favorite dependency injector.
   * @param string            $profileClass The configuration profile's fully qualified class name.
   */
  function __construct (InjectorInterface $injector, $profileClass)
  {
    $this->injector = $injector;
    $injector
      ->share ($injector)
      ->alias (InjectorInterface::class, get_class ($injector))
      ->alias (ProfileInterface::class, $profileClass);
  }

  /**
   * Bootstraps the application.
   *
   * @param string $rootDir  The application's root directory path.
   * @param int    $urlDepth How many URL segments should be stripped when calculating the application's root URL.
   */
  function run ($rootDir, $urlDepth = 0)
  {
    $rootDir = normalizePath ($rootDir);

    // Initialize some settings from environment variables

    if (file_exists ("$rootDir/.env")) {
      $dotenv = new Dotenv ($rootDir);
      $dotenv->load ();
    }

    /** @var KernelSettings $kernelSettings */
    $kernelSettings = $this->injector
      ->share (KernelSettings::class, 'app')
      ->make (KernelSettings::class);

    $kernelSettings->isConsoleBased = true;
    $kernelSettings->setRootDir ($rootDir);

    // Setup the console.

    $console  = new SymfonyConsole ('Workman Task Runner');
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

    // Create a new application instance.

    $consoleApp = new ConsoleApplication ($io, $settings, $console, $this->injector);
    $this->injector->share ($consoleApp);

    // Run the framework's console subsystem, which then runs the terminal-based application.

    $consoleApp->setupStandardIO ($_SERVER['argv']);
    $consoleApp->execute ();
  }

}
