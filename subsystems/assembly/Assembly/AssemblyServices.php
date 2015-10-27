<?php
namespace Selenia\Assembly;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class AssemblyServices implements ServiceProviderInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (ModulesManager::ref);
    $injector->share (ModuleServices::ref);
  }
}
