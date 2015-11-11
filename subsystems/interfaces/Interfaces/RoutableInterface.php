<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;

/**
 * Represents an HTTP request handler that is routing-aware.
 * <p>The handler will handle the request only if its URL matches a specific one.
 * <p>URLs are matched segment by segment (path locations). Each handler will match a single location and it should
 * delegate the handling of further locations to other handlers.
 * <p>When a handler returns a response, no further handlers will be called.
 * <p>If a handler does not match a route, it should call `$route->next()`. If it passes a new response
 * object to `next()`, that response will replace the current one and it will be passed along to the next handlers.
 */
interface RoutableInterface
{
  /**
   * @param RouterInterface $router
   * @return ResponseInterface|false
   */
  function __invoke (RouterInterface $router);

}
