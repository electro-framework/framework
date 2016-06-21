<?php
namespace Electro\Navigation\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * <p>You should register this middleware right before the router.
 */
class NavigationMiddleware implements RequestHandlerInterface
{
  /**
   * @var NavigationInterface
   */
  private $navigation;

  public function __construct (NavigationInterface $navigation)
  {
    $this->navigation = $navigation;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->navigation->request ($request);
    return $next();
  }

}
