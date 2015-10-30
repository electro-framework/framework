<?php
namespace Selenia\Authentication\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Authentication\Exceptions\AuthenticationException;
use Selenia\Http\Services\Redirection;
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

  /**
   * @return bool|string
   * <li> True is a login form should be displayed.
   * <li> False to proceed as a normal request.
   * <li> <code>'retry'</code> to retry GET request by redirecting to same URI.
   */
  protected function authenticate ()
  {
    global $application, $session;
    $authenticate = false;
    if (isset($session) && $application->requireLogin) {
      $this->getActionAndParam ($action, $param);
      $authenticate = true;
      if ($action == 'login') {
        $prevPost = get ($_POST, '_prevPost');
        try {
          $this->login ();
          if ($prevPost)
            $_POST = unserialize (urldecode ($prevPost));
          else $_POST = [];
          $_REQUEST = array_merge ($_POST, $_GET);
          if (empty($_POST))
            $_SERVER['REQUEST_METHOD'] = 'GET';
          if ($this->wasPosted ())
            $authenticate = false; // user is now logged in; proceed as a normal request
          else $authenticate = 'retry';
        } catch (AuthenticationException $e) {
          $this->setStatus (FlashType::WARNING, $e->getMessage ());
          // note: if $prevPost === false, it keeps that value instead of (erroneously) storing the login form data
          if ($action)
            $this->prevPost = isset($prevPost) ? $prevPost : urlencode (serialize ($_POST));
        }
      }
      else {
        $authenticate = !$session->validate ();
        if ($authenticate && $action)
          $this->prevPost = urlencode (serialize ($_POST));
        if ($this->isWebService) {
          $username = get ($_SERVER, 'PHP_AUTH_USER');
          $password = get ($_SERVER, 'PHP_AUTH_PW');
          if ($username) {
            $session->login ($application->defaultLang, $username, $password);
            $authenticate = false;
          }
        }
      }
    }
    return $authenticate;
  }

}
