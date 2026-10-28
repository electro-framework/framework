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
    if (!$this->httpSettings->useCsrfToken ())
      return $next();

    $session = $this->session;

    // The token is generated only once per session (like Laravel does), not on every request. It is lost, and therefore
    // recreated here, when the session is destroyed (ex: on logout) or expires.
    if (!$session->token ())
      $session->regenerateToken ();

    // Requests that do not change state are not verified.
    if (in_array ($request->getMethod (), ['GET', 'HEAD', 'OPTIONS']))
      return $next();

    $sessionToken = (string)$session->token ();
    $sentToken    = $this->getSentToken ($request);

    if ($sessionToken !== '' && $sentToken !== '' && hash_equals ($sessionToken, $sentToken))
      return $next();

    return $response->withStatus (403, 'Csrf Token invalid.');
  }

  /**
   * Gets the token sent with the request, either from the X-CSRF-Token header or from the form fields `token` or
   * `_token` (only for form-encoded bodies).
   *
   * @param ServerRequestInterface $request
   * @return string An empty string if no token was sent.
   */
  private function getSentToken (ServerRequestInterface $request)
  {
    $header = $request->getHeaderLine ('X-CSRF-Token');
    if ($header !== '')
      return $header;

    $contentType = $request->getHeaderLine ('Content-Type');
    if ($contentType == 'application/x-www-form-urlencoded' || strpos ($contentType, 'multipart/form-data') !== false) {
      $post = $request->getParsedBody ();
      if (is_array ($post))
        foreach (['token', '_token'] as $field)
          if (isset($post[$field]) && is_string ($post[$field]))
            return $post[$field];
    }
    return '';
  }
}
