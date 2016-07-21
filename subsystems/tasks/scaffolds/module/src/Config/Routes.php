<?php
namespace Ptpt\Backoffice\Config;

use Electro\Http\Lib\Http;
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
        '.' => function () use ($response) { return Http::response ($response, 'It works!'); },
      ])
      ->__invoke ($request, $response, $next);
  }

}
