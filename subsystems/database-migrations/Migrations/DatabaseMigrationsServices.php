<?php
namespace Selenia\Migrations;

use Selenia\Assembly\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class DatabaseMigrationsServices implements ServiceProviderInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
  }

  function register (InjectorInterface $injector)
  {
    ModuleOptions (dirname (__DIR__), [
      'tasks' => 'Selenia\Migrations\Commands',
    ]);
  }

}
