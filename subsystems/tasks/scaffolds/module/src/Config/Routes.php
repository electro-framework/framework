<?php
namespace ___NAMESPACE___\Config;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Routes implements RequestHandlerInterface
{
  /** @var RouterInterface */
  private $router;

  public function __construct (RouterInterface $router)
  {
    $this->router = $router;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $this->router
      ->add ([
        '.' => htmlResponse ('It works!'),
      ])
      ->__invoke ($request, $response, $next);
  }

}
