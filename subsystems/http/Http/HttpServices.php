<?php
namespace Selenia\Http;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class HttpServices implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\ResponseFactoryInterface', 'Selenia\Http\ResponseFactory')
      ->alias ('Selenia\Interfaces\ResponseSenderInterface', 'Selenia\Http\ResponseSender');
  }

}
