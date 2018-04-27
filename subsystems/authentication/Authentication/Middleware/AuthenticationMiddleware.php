<?php
namespace Electro\Authentication\Middleware;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
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
   * @var AuthenticationSettings
   */
  private $settings;
  /**
   * @var UserInterface
   */
  private $user;

  function __construct (KernelSettings $kernelSettings, SessionInterface $session, RedirectionInterface $redirection,
                        AuthenticationSettings $settings, UserInterface $user)
  {
    $this->session        = $session;
    $this->kernelSettings = $kernelSettings;
    $this->redirection    = $redirection;
    $this->settings       = $settings;
    $this->user           = $user;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);
    $serverRequest = ServerRequest::fromGlobals ();
    $cookies       = RequestCookies::createFromRequest ($serverRequest);

    // LOG OUT

    if ($request->getUri () == $this->settings->getLogoutUrl ()) {
      $response = $this->redirection->home ();

      $this->session->logout ();
      if ($cookies->has ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName)) {
        $cookie   =
          SetCookie::thatDeletesCookie ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName,
            $request->getAttribute ('baseUri'));
        $response = $cookie->addToResponse ($response);
      }
      return $response;
    }

    switch ($request->getMethod ()) {
      case 'GET':
        // LOG IN
        if (!$this->session->loggedIn ()) {
          if ($cookies->has ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName)) {
            $token = $cookies->get ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName)
                             ->getValue ();
            $user  = $this->user;
            $user->findByToken ($token);
            $this->session->setUser ($user);
            return $next();
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
