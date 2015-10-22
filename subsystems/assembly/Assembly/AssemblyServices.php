<?php
namespace Selenia\Assembly;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class AssemblyServices implements ServiceProviderInterface
{
  function boot () { }

  function register (InjectorInterface $injector)
  {
    $injector->share ('Selenia\ModulesApi');
  }

}
