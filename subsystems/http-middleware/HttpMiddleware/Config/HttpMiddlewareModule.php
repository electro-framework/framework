<?php
namespace Selenia\HttpMiddleware\Config;

use Selenia\HttpMiddleware\Services\MiddlewareStack;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareStackInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class HttpMiddlewareModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\MiddlewareStackInterface', MiddlewareStack::ref)
      ->share (MiddlewareStack::ref)
      ->delegate ('Psr\Http\Message\ServerRequestInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentRequest ();
      })
      ->delegate ('Psr\Http\Message\ResponseInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentResponse ();
      });
  }

}
