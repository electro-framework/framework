<?php
namespace Electro\Routing\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;

class EmailModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector->execute (function ($debugConsole) use ($injector) {

//      $injector
//        ->delegate (Swift_Mailer::class, function () use ($injector) {
//
//        });
    });

  }

}
