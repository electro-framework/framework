<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Represents an HTTP request handler that is routing-aware.
 * <p>The handler will decide how to handle the request.
 * <p>When the framework is routing, URLs are matched segment by segment (path locations). Each handler will match a
 * single location and it should delegate the handling of further locations to other handlers.
 * <p>When a handler returns a response, no further handlers will be called.
 * <p>If a handler decides to not handle the request, it should return `false`.
 */
interface RoutableInterface
{
  /**
   * Handles the HTTP request.
   * @param RouterInterface $router
   * @return ResponseInterface|false The generated response, or <kbd>false</kbd> If the request was not handled.
   */
  function __invoke (RouterInterface $router);

}
