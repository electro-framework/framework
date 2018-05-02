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
    if (!$this->httpSettings->useCsrfToken ()) return $next();

    $session       = $this->session;
    $errorMessage  = 'Csrf Token invalid.';
    $requestMethod = $request->getMethod ();

    $headerXCSRFToken = $request->getHeaderLine ('X-CSRF-Token');
    if (exists ($headerXCSRFToken)) {
      if ($headerXCSRFToken == $session->token ()) return $response = $next();
    }
    else {
      if ($requestMethod == 'GET') {
        $session->regenerateToken ();
        return $next();
      }
      else if ($requestMethod == 'POST') {
        $headerContentType = $request->getHeaderLine ('Content-Type');
        if ($headerContentType == "application/x-www-form-urlencoded" ||
            $headerContentType == "multipart/form-data"
        ) {
          $post = $request->getParsedBody ();

          if (array_key_exists ('token', $post)) {
            if ($post['token'] == $session->token ()) return $next();
          }
        }
      }
    }
    return $response->withStatus (403, $errorMessage);
  }
}