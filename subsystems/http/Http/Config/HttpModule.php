<?php
namespace Selenia\Http\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Http\Services\Redirection;
use Selenia\Http\Services\ResponseFactory;
use Selenia\Http\Services\ResponseSender;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\ResponseFactoryInterface;
use Selenia\Interfaces\Http\ResponseSenderInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;
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
