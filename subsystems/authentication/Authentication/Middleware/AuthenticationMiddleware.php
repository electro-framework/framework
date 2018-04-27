<?php
namespace Electro\Authentication\Middleware;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Sessions\Config\SessionSettings;
use GuzzleHttp\Psr7\ServerRequest;
use HansOtt\PSR7Cookies\RequestCookies;
use HansOtt\PSR7Cookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * TODO: handle expired session on POST request (i.e. retry the POST when authenticated).
 * TODO: handle HTTP Basic Authentication.
 * TODO: handle API authentication (OAuth, etc).
 */
class AuthenticationMiddleware implements RequestHandlerInterface
{
  private $kernelSettings;
  /**
   * @var RedirectionInterface
   */
  private $redirection;
  private $session;
  /**
   * @var SessionSettings
   */
  private $sessionSettings;
  /**
   * @var AuthenticationSettings
   */
  private $settings;
  /**
   * @var UserInterface
   */
  private $user;

  function __construct (KernelSettings $kernelSettings, SessionInterface $session, RedirectionInterface $redirection,
                        AuthenticationSettings $settings, UserInterface $user, SessionSettings $sessionSettings)
  {
    $this->session         = $session;
    $this->kernelSettings  = $kernelSettings;
    $this->redirection     = $redirection;
    $this->settings        = $settings;
    $this->user            = $user;
    $this->sessionSettings = $sessionSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);
    $cookies  = RequestCookies::createFromRequest ($request);
    $settings = $this->sessionSettings;

    $sessionName = $settings->sessionName;
    $rememberMeTokenName = $settings->rememberMeTokenName;
    $cookieName = $sessionName . "_" . $rememberMeTokenName;

    // LOG OUT

    if ($request->getUri () == $this->settings->getLogoutUrl ()) {
      $response = $this->redirection->home ();

      $this->session->logout ();
      if ($cookies->has ($cookieName)) {
        $cookie   =
          SetCookie::thatDeletesCookie ($cookieName,
            $request->getAttribute ('baseUri'));
        $response = $cookie->addToResponse ($response);
      }
      return $response;
    }

    switch ($request->getMethod ()) {
      case 'GET':
        // LOG IN
        if (!$this->session->loggedIn ()) {
          if ($cookies->has ($cookieName)) {
            $token = $cookies->get ($cookieName)
                             ->getValue ();
            $user  = $this->user;
            if ($user->findByToken ($token)) {
              $this->session->setUser ($user);
              return $next();
            }
          }
          return $this->redirection->guest ($this->settings->getLoginUrl ());
        }
        break;
    }

    try {
      return $next();
    }
    catch (AuthenticationException $flash) {
      $this->session->flashMessage ($flash->getMessage (), $flash->getCode (), $flash->getTitle ());

      $post = $request->getParsedBody ();
      if (is_array ($post))
        $this->session->flashInput ($post);

      $this->session->reflashPreviousUrl ();
      return $this->redirection->refresh ();
    }
  }
}