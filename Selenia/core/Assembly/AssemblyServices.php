<?php
namespace Selenia\Core\Assembly;

use Selenia\Application;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Core\Assembly\Services\ModulesManager;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class AssemblyServices implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (ModulesManager::ref)
      ->share (ModuleServices::ref)
      ->share (ModulesRegistry::ref)
      ->delegate (ModulesRegistry::ref, function (Application $app) {
        $registry = new ModulesRegistry ($app);
        $registry->load ();
        return $registry;
      });
  }
}
