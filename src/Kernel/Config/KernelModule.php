<?php
namespace Electro\Kernel\Config;

use Electro\Exceptions\ExceptionWithTitle;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\Migrations\MigrationsInterface;
use Electro\Kernel\Services\Kernel;
use Electro\Kernel\Services\ModulesInstaller;
use Electro\Kernel\Services\ModulesRegistry;

class KernelModule
{
  const TASK_RUNNER_NAME = 'workman';

  static function register (InjectorInterface $injector)
  {
    $injector
      ->alias (KernelInterface::class, Kernel::class)
      ->share (ModulesRegistry::class)
      ->prepare (ModulesRegistry::class, function (ModulesRegistry $registry) use ($injector) {
        if (!$registry->load ()) {
          $settings = $injector->make (KernelSettings::class);
          if (!$settings->isConsoleBased) {
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
      // MigrationsInterface must be lazy-loaded on demand.
      ->define (ModulesInstaller::class, [
        ':migrationsAPIFactory' => $injector->makeFactory (MigrationsInterface::class),
      ]);
  }

}
