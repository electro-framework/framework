<?php
namespace Electro\Http\Config;

use Electro\Http\Services\Redirection;
use Electro\Http\Services\ResponseFactory;
use Electro\Http\Services\ResponseSender;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\Http\ResponseSenderInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Bootstrapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use const Electro\Kernel\Services\REGISTER_SERVICES;

/**
 * ### Notes:
 *
 * - `ServerRequestInterface` and `ResponseInterface` instances are not shared, and injecting them always yields new
 *   pristine instances.
 *   > **Note:** when cloning `ResponseInterface` instances, never forget to also clone their `body` streams.
 * - Injected `HandlerPipelineInterface` instances are not shared.
 */
class HttpModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->alias (RedirectionInterface::class, Redirection::class)
        ->alias (ResponseFactoryInterface::class, ResponseFactory::class)
        ->alias (ResponseSenderInterface::class, ResponseSender::class)
        ->alias (ServerRequestInterface::class, ServerRequest::class)
        ->alias (ResponseInterface::class, Response::class);
    });
  }

}
