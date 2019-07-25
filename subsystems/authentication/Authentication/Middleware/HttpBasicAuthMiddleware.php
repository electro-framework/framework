<?php
namespace Electro\Authentication\Middleware;

use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpBasicAuthMiddleware implements RequestHandlerInterface
{
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  function __construct (KernelSettings $kernelSettings, SessionInterface $session, UserInterface $user)
  {
    $this->session = $session;
    $this->user    = $user;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $authHeader = $request->getHeaderLine ('Authorization');
    if ($authHeader) {
      $authHeader  = explode (' ', $authHeader, 2);
      $type        = $authHeader[0] ?? '';
      $credentials = base64_decode ($authHeader[1] ?? '');
      if ($type == 'Basic') {
        $credentials = explode (':', $credentials, 2);
        if (count ($credentials) == 2) {
          list ($username, $password) = $credentials;
          if ($this->user->findByName ($username)) {
            if ($this->user->verifyPassword ($password)) {
              $this->session->setUser ($this->user);
              return $next ();
            }
          }
        }
      }
    }
    return Http::response ($response, 'Unauthorized', 'text/plain', Http::UNAUTHORIZED)
               ->withHeader ('WWW-Authenticate',
                 sprintf ('Basic charset=UTF-8'));
  }
}
