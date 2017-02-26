<?php
namespace Electro\Http\Middleware;

use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Prepares a request for Fetch-enabled page rendering.
 *
 * <p>It sets the `isFetch` request attribute to indicate whether the request is a Fetch (aka. AJAX) request or a normal
 * page request.
 *
 * <p>If it is a Fetch request and the client's current URL is a different page than the one being requested, it
 * cancels the request handling and outputs a blank response immediately. The client-side library that sent the
 * request will then perform a normal navigation to the requested page.
 */
class FetchMiddleware implements RequestHandlerInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $isFetch  = $request->getHeaderLine ('X-Requested-With') == 'XMLHttpRequest';
    $referer  = Http::relativePathOf ($request->getHeaderLine ('Referer'), $request);
    $samePage = $referer == $request->getAttribute ('virtualUri');

    $request  = $request->withAttribute ('refererVirtualUri', $referer)
                        ->withAttribute ('isFetch', $isFetch)
                        ->withAttribute ('isSamePage', $samePage);
    return $next ($request);
  }

}
