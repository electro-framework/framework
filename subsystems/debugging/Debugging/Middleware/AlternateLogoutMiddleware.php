<?php

namespace Electro\Debugging\Middleware;

use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware that allows logging out via web console (this must run after the session middleware)
 */
class AlternateLogoutMiddleware implements RequestHandlerInterface
{
  /**
   * Request parameter to be added to the current URL for forcing a log out.
   * The URL path is preserved so that we may clear the correct cookie for the current URL.
   * This is triggered from a web console button.
   */
  const LOGOUT_PARAM = 'debug-logout';
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (SessionInterface $session)
  {
    $this->session = $session;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $uri = $request->getUri ();
    if (str_endsWith ($uri->getQuery (), self::LOGOUT_PARAM)) {
      $this->session->logout ();
      $query = substr ($uri->getQuery (), 0, -strlen (self::LOGOUT_PARAM));
      return Http::redirect ($response, (string)$uri->withQuery ($query));
    }
    return $next ();
  }

}
