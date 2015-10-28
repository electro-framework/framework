<?php
namespace Selenia\Http\Config;

use Selenia\Http\Services\ResponseFactory;
use Selenia\Http\Services\ResponseSender;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class HttpModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\ResponseFactoryInterface', ResponseFactory::ref)
      ->alias ('Selenia\Interfaces\ResponseSenderInterface', ResponseSender::ref);
  }

}
