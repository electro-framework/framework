<?php
namespace Selenia\Authentication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Http\Redirection;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;

/**
 *
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
  private $app;
  /**
   * @var Redirection
   */
  private $redirection;
  private $session;

  function __construct (Application $app, SessionInterface $session, Redirection $redirection)
  {
    $this->session     = $session;
    $this->app         = $app;
    $this->redirection = $redirection;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($request->getMethod () == 'POST') {
      $post   = $request->getParsedBody ();
      $action = get ($post, '_action');
      switch ($action) {
        case 'login':
          $username = get ($post, 'username');
          $password = get ($post, 'password');
          $lang     = get ($post, 'lang');
          if ($lang)
            $this->session->setLang ($lang);
          $this->login ($username, $password);
          return $this->redirection->intended ($this->app->baseURI);
        case 'logout':
          $this->session->logout ();
          return $this->redirection->home ();
      }
    }

    return $next();
  }

  /**
   * Attempts to log in the user with the given credentials.
   * @param string $username
   * @param string $password
   * @throws AuthenticationException If the login fails.
   */
  function login ($username, $password)
  {
    global $application;
    if (empty($username))
      throw new AuthenticationException (AuthenticationException::MISSING_INFO);
    else {
      /** @var UserInterface $user */
      $user = new $application->userModel;
      $user->findByName ($username);
      if (!$user->findByName ($username))
        throw new AuthenticationException (AuthenticationException::UNKNOWN_USER);
      else if (!$user->verifyPassword ($password))
        throw new AuthenticationException (AuthenticationException::WRONG_PASSWORD);
      else if (!$user->active ())
        throw new AuthenticationException (AuthenticationException::DISABLED);
      else {
        try {
          $user->onLogin ();
          $this->session->setUser ($user);
        } catch (\Exception $e) {
          throw new AuthenticationException($e->getMessage ());
        }
      }
    }
  }

}
