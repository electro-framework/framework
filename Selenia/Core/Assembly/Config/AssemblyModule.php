<?php
namespace Selenia\Core\Assembly\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Core\Assembly\Services\ModulesInstaller;
use Selenia\Core\Assembly\Services\ModulesLoader;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Migrations\Config\MigrationsSettings;

class AssemblyModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ModulesLoader::class)
      ->share (ModuleServices::class)
      ->share (ModulesRegistry::class)
      ->prepare (ModulesRegistry::class, function (ModulesRegistry $registry) {
        $registry->load ();
      })
      ->prepare (ModulesInstaller::class, function (ModulesInstaller $installer) {
        // Configure the installer to use migrations only if the migrations module is available.
        if (class_exists (MigrationsSettings::class))
          $installer->migrationsSettings = new MigrationsSettings;
      });
  }
}
