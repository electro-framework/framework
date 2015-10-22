<?php
namespace Selenia\Migrations;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class DatabaseMigrationsServices implements ServiceProviderInterface
{
  function boot () { }

  function register (InjectorInterface $injector)
  {
    ModuleOptions (dirname (__DIR__), [
      'tasks' => 'Selenia\Migrations\Commands',
    ]);
  }

}
