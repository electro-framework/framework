<?php
namespace Selenia\Logging;

use Monolog\Logger;
use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class LoggingServices implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share ('Psr\Log\LoggerInterface')
      ->delegate ('Psr\Log\LoggerInterface', function (Application $app) {
        return new Logger ('main', $app->logHandlers);
      });
  }

}
