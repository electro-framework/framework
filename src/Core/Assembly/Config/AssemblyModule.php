<?php
namespace Electro\Core\Assembly\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Core\Assembly\Services\ModulesInstaller;
use Electro\Core\Assembly\Services\ModulesLoader;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Core\WebApplication\ApplicationMiddlewareAssembler;
use Electro\Exceptions\ExceptionWithTitle;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\ApplicationMiddlewareAssemblerInterface;
use Electro\Interfaces\Migrations\MigrationsInterface;

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
      // MigrationsInterface must be lazy-loaded on demand.
      ->define (ModulesInstaller::class, [
        ':migrationsAPIFactory' => $injector->makeFactory (MigrationsInterface::class),
      ])
      // This can be overridden later, usually by a private application module.
      ->alias (ApplicationMiddlewareAssemblerInterface::class, ApplicationMiddlewareAssembler::class);
  }

}
