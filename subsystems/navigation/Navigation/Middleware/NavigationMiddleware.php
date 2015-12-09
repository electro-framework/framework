<?php
namespace Selenia\Navigation\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;

/**
 * Sets up the application's navigation.
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
