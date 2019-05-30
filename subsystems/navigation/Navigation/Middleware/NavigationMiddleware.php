<?php

namespace Electro\Navigation\Middleware;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Navigation\Config\NavigationSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines the navigation tree by calling each registered navigation provider and registers a CurrentRequest
 * object that will allow the Navigation service to, later on, compute the navigation URLs based on the latest
 * routing info.
 */
class NavigationMiddleware implements RequestHandlerInterface
{
  /** @var CurrentRequestInterface */
  private $currentRequest;
  /** @var InjectorInterface */
  private $injector;
  /** @var NavigationInterface */
  private $navigation;
  /** @var NavigationSettings */
  private $settings;

  public function __construct (NavigationInterface $navigation, NavigationSettings $settings,
                               InjectorInterface $injector, CurrentRequestInterface $currentRequest)
  {
    $this->navigation     = $navigation;
    $this->settings       = $settings;
    $this->injector       = $injector;
    $this->currentRequest = $currentRequest;
    $this->navigation->setRequest ($currentRequest);
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    // Note: the Session and CurrentRequest services can be used by navigation providers to define dynamic navigation trees based on the request.

    $this->currentRequest->setInstance ($request);

    // Setup navigation from each registered provider.
    foreach ($this->settings->getProviders () as $provider) {
      if (is_string ($provider))
        $provider = $this->injector->make ($provider);
      $provider->defineNavigation ($this->navigation);
    }

    return $next ();
  }

}
