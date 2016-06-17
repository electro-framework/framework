<?php
namespace Electro\Authentication\Middleware;
use Electro\Application;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Plugins\AdminInterface\Config\PlatformModule;

/**
 *
 */
class AuthenticationMiddleware implements RequestHandlerInterface
{
  private $app;
  /**
   * @var RedirectionInterface
   */
  private $redirection;
  private $session;

  function __construct (Application $app, SessionInterface $session, RedirectionInterface $redirection)
  {
    $this->session     = $session;
    $this->app         = $app;
    $this->redirection = $redirection;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);

    switch ($request->getMethod ()) {
      case 'GET':
        if (!$this->session->loggedIn ())
          return $this->redirection->guest ($this->app->loginFormUrl);
        break;
      case 'POST':
        $post   = $request->getParsedBody ();
        $action = get ($post, PlatformModule::ACTION_FIELD);
        switch ($action) {
          case 'logout':
            $this->session->logout ();
            return $this->redirection->home ();
        }
    }

    return $next();
  }

  /**
   * TODO: authenticate via HTTP Basic Authentication
   * This code was copy/pasted fom an old controller; it must be rewritten.
   *
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
