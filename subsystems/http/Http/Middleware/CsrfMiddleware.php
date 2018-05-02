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
    $this->session = $session;
    $this->httpSettings = $httpSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $session = $this->session;

    if($this->httpSettings->useCsrfToken ()){
      if ($request->getMethod () == 'POST') {
        $post = $request->getParsedBody ();

        if (array_key_exists ('token', $post)) {
          if ($post['token'] != $session->token ()) {
            return $response->withStatus (403,'Csrf Token invalid.');
          }
          else return $response = $next();
        }
        else {
          return $response->withStatus (403,'Csrf Token not exist.');
        }
      }
      else if ($request->getMethod () == 'GET') {
        $session->regenerateToken ();

        /** @var ResponseInterface $response */
        $response = $next();
      }
    }
    else {
      /** @var ResponseInterface $response */
      $response = $next();
    }
    return $response;
  }
}