<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 * <p>A handler may generate an HTTP response and/or route to other handlers.
 * <p>The request will traverse a tree of interconnected handlers, until a full HTTP response is generated or the
 * tree traversal is completed.
 *
 * > Note that not all nodes on the tree will be visited, as most routes will not match the request's URL.
 *
 * If the handler tree is exhausted, the router sends the request to the next handler on the application's main
 * request handling pipeline.
 *
 * > **Note:** instances implementing this interface **MUST** be immutable.
 */
interface RouterInterface extends RequestHandlerPipelineInterface
{
  /**
   * @param \Traversable|array|string|callable $routable
   * @return ResponseInterface|null
   */
  function route ($routable);

  /**
   * Returns a new router instance with a new context for routing.
   * @param ServerRequestInterface|null $request
   * @param ResponseInterface|null      $response
   * @param callable|null               $next
   * @return $this
   */
  function with (ServerRequestInterface $request = null, ResponseInterface $response = null, callable $next = null);

}
