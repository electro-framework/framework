<?php
namespace Selenia\Subsystems\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Subsystems\Http\Contracts\MiddlewareInterface;

/**
 * Initializes Search Engine Optimization information for the current page.
 */
class SEOMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
    global $application, $controller;
    if (isset($application->routingMap)) {
      if (isset($controller->sitePage->keywords))
        $controller->page->keywords =
          isset($controller->lang) ? get ($controller->sitePage->keywords, $controller->lang, '') : $controller->sitePage->keywords;
      if (isset($controller->sitePage->description))
        $controller->page->description =
          isset($controller->lang) ? get ($controller->sitePage->description, $controller->lang, '') : $controller->sitePage->description;
    }
  }
}
