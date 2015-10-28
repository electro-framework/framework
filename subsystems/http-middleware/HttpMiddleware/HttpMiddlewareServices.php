<?php
namespace Selenia\HttpMiddleware;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareStackInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class HttpMiddlewareServices implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->alias ('Selenia\Interfaces\MiddlewareStackInterface', 'Selenia\HttpMiddleware\MiddlewareStack')
      ->share ('Selenia\HttpMiddleware\MiddlewareStack')
      ->delegate ('Psr\Http\Message\ServerRequestInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentRequest ();
      })
      ->delegate ('Psr\Http\Message\ResponseInterface', function (MiddlewareStackInterface $middlewareStack) {
        return $middlewareStack->getCurrentResponse ();
      });
  }

}
