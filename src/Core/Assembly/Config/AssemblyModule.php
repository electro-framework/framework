<?php
namespace Electro\Core\Assembly\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Core\Assembly\Services\ModulesInstaller;
use Electro\Core\Assembly\Services\ModulesLoader;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Exceptions\ExceptionWithTitle;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Plugins\IlluminateDatabase\Migrations\Config\MigrationsSettings;

class AssemblyModule implements ServiceProviderInterface
{
  const TASK_RUNNER_NAME = 'workman';

  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ModulesLoader::class)
      ->share (ModuleServices::class)
      ->share (ModulesRegistry::class)
      ->prepare (ModulesRegistry::class, function (ModulesRegistry $registry) use ($injector) {
        if (!$registry->load ()) {
          $app = $injector->make (Application::class);
          if (!$app->isConsoleBased) {
            $runner = self::TASK_RUNNER_NAME;
            throw new ExceptionWithTitle ("The application's runtime configuration is not initialized.",
              "Please run <kbd>$runner</kbd> on the command line.");
          }
          /** @var ModulesInstaller $installer */
          // Note: to prevent a cyclic dependency exception, $registry must be passed to the ModulesInstaller's
          // constructor.
          $installer = $injector->make (ModulesInstaller::class, [':modulesRegistry' => $registry]);
          $installer->rebuildRegistry ();
        }
      })
      ->prepare (ModulesInstaller::class, function (ModulesInstaller $installer) {
        // Configure the installer to use migrations only if the migrations module is available.
        if (class_exists (MigrationsSettings::class))
          $installer->migrationsSettings = new MigrationsSettings;
      });
  }
}
