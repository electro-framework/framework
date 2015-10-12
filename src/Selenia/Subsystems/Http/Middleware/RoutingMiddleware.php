<?php
namespace Selenia\Subsystems\Http\Middleware;
use Auryn\Injector;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Exceptions\HttpException;
use Selenia\Subsystems\Http\Contracts\MiddlewareInterface;
use Selenia\ModuleLoader;
use Selenia\Router;

/**
 *
 */
class RoutingMiddleware implements MiddlewareInterface
{
  private $injector;

  function __construct (Injector $injector)
  {
    $this->injector = $injector;
  }

  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
    try {
      $router = Router::route ();
      $this->injector->share ($router);
    } catch (HttpException $e) {
      @ob_get_clean ();
      http_response_code ($e->getCode ());
      echo $e->getMessage ();
      exit;
    }
    return $router->controller->__invoke ($request, $response, $next);
  }
}
