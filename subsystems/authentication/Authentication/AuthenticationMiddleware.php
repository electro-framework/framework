<?php
namespace Selenia\Authentication;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\SessionInterface;

/**
 *
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
  private $app;
  private $session;

  function __construct (Application $app, SessionInterface $session)
  {
    $this->session = $session;
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($request->getMethod() == 'POST' && $request->getAttribute('VURI') == 'login') {
      $post = $request->getParsedBody();
      $username = get($post, 'username');
      $password = get ($post, 'password');
      $lang = get ($post, 'lang', $this->app->defaultLang);
      $this->session->setLang($lang);
      $this->session->login($username, $password);
    }

    return $next();
  }
}
