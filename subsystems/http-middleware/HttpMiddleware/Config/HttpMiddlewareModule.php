<?php
namespace Selenia\HttpMiddleware\Config;

use Selenia\HttpMiddleware\Services\MiddlewareStack;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareStackInterface;
use Selenia\Interfaces\ServiceProviderInterface;

/**
 * ### Notes:
 *
 * - Injected `MiddlewareStackInterface` instances are not shared.
 */
class HttpMiddlewareModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\MiddlewareStackInterface', MiddlewareStack::class)
      ->delegate ('Psr\Http\Message\ServerRequestInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentRequest ();
      })
      ->delegate ('Psr\Http\Message\ResponseInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentResponse ();
      });
  }

}
