<?php
namespace Selenia\Core\Assembly\Config;

use Selenia\Application;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Core\Assembly\Services\ModulesManager;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class AssemblyServiceProvider implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ModulesManager::class)
      ->share (ModuleServices::class)
      ->share (ModulesRegistry::class)
      ->delegate (ModulesRegistry::class, function (Application $app) {
        $registry = new ModulesRegistry ($app);
        $registry->load ();
        return $registry;
      });
  }
}
