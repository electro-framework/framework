<?php
namespace Electro\Routing\Middleware;

use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * It allows a designer to rapidly prototype the application by automatically providing routing for URLs matching files
 * on the views directories, which will be routed to a generic controller that will load the matched view.
 *
 * <p>**Note:** currently, this middleware only supports Matisse templates.
 *
 * <p>**This is NOT recommended for production!**
 *
 * <p>You should register this middleware right before the router, but only if `devEnv = false`.
 */
class AutoRoutingMiddleware implements RequestHandlerInterface
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $URL = $request->getAttribute ('virtualUri');
    if ($URL == '') $URL = 'index';
    elseif (substr ($URL, -1) == '/') $URL = $URL . 'index';

    try {
      $routable = page ($URL);
      $handler  = $this->injector->execute ($routable);
      return $handler ($request, $response);
    }
    catch (FileNotFoundException $e) {
      return $next ();
    }
  }

}
