<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 * <p>A handler (also called a *routable*) may generate an HTTP response and/or route to other handlers.
 * <p>The request will traverse a graph of interconnected handlers, until a full HTTP response is generated or the
 * graph is exhausted.
 *
 * ### Notes
 * - Instances implementing this interface **MUST** be immutable objects.
 * - `for()` creates new instances.
 */
interface RouterInterface
{
  function for (ServerRequestInterface $request = null, ResponseInterface $response = null, callable $next = null);

  function route ($routable);

  /**
   * Creates a new redirection HTTP response.
   *
   * <p>This is a convenience method that saves you from having to inject the redirection service on routers.
   * @return RedirectionInterface
   */
  function redirection ();

  /**
   * The HTTP request being routed.
   *
   * @return ServerRequestInterface
   */
  function request ();

  /**
   * The HTTP response that was generated so far.
   * <p>It is initially empty, but some routables along the route may generate and accumulate partial content for it.
   *
   * @return ResponseInterface
   */
  function response ();

  /**
   * The target route for this router instance.
   * @return RouteInterface
   */
  function route ();

}
