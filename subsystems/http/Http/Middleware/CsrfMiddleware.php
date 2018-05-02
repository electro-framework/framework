<?php
namespace Electro\Http\Middleware;

use Electro\Http\Config\HttpSettings;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Verifies CSRF tokens for form POST requests.
 */
class CsrfMiddleware implements RequestHandlerInterface
{

  /**
   * @var HttpSettings
   */
  private $httpSettings;
  private $session;

  /**
   * CsrfMiddleware constructor.
   *
   * @param SessionInterface $session
   * @param HttpSettings     $httpSettings
   */
  public function __construct (SessionInterface $session, HttpSettings $httpSettings)
  {
    $this->session      = $session;
    $this->httpSettings = $httpSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $session      = $this->session;
    $post         = $request->getParsedBody ();
    $errorMessage = 'Csrf Token invalid.';

    if (exists ($request->getHeader ('X-CSRF-Token'))) {
      if ($this->httpSettings->useCsrfToken ()) {
        if (array_key_exists ('token', $post)) {
          if ($post['token'] != $session->token ()) return $response->withStatus (403, $errorMessage);
          else return $response = $next();
        }
        else return $response->withStatus (403, $errorMessage);
      }
    }
    else {
      if ($this->httpSettings->useCsrfToken ()) {
        if ($request->getMethod () == 'GET') $session->regenerateToken ();
        else if ($request->getMethod () == 'POST') {
          if ($request->getHeader ('Content-Type')[0] == "application/x-www-form-urlencoded" ||
              $request->getHeader ('Content-Type')[0] == "multipart/form-data"
          ) {
            if (array_key_exists ('token', $post)) {
              if ($post['token'] != $session->token ()) return $response->withStatus (403, $errorMessage);
            }
            else return $response->withStatus (403, $errorMessage);
          }
          else return $response->withStatus (403, $errorMessage);
        }
      }
      $response = $next();
    }
    return $response;
  }
}
