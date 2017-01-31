<?php

namespace Electro\Navigation\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Assigns the current HTTP request instance to the navigation service and initializes the service.
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
    $this->navigation->setRequest ($request);
    return $next ();
  }

}
