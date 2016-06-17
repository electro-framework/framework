<?php
namespace Electro\Http\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Http\Services\Redirection;
use Electro\Http\Services\ResponseFactory;
use Electro\Http\Services\ResponseSender;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\Http\ResponseSenderInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * ### Notes:
 *
 * - `ServerRequestInterface` and `ResponseInterface` instances are not shared, and injecting them always yields new
 *   pristine instances.
 *   > **Note:** when cloning `ResponseInterface` instances, never forget to also clone their `body` streams.
 * - Injected `HandlerPipelineInterface` instances are not shared.
 */
class HttpModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (RedirectionInterface::class, Redirection::class)
      ->alias (ResponseFactoryInterface::class, ResponseFactory::class)
      ->alias (ResponseSenderInterface::class, ResponseSender::class)
      ->alias (ServerRequestInterface::class, ServerRequest::class)
      ->alias (ResponseInterface::class, Response::class);
  }

}
