<?php
namespace Selenia\Database\Config;

use PhpKit\Connection;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class DatabaseModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector->share ((new Connection)->getFromEnviroment ());
  }

}
