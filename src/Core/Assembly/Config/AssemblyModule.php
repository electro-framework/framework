<?php
namespace Electro\Core\Assembly\Config;

use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Core\Assembly\Services\ModulesInstaller;
use Electro\Core\Assembly\Services\ModulesLoader;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Migrations\Config\MigrationsSettings;

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
