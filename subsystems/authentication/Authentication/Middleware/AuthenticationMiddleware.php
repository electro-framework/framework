<?php
namespace Electro\Authentication\Middleware;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Kernel\Config\KernelSettings;
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

  function __construct (KernelSettings $kernelSettings, SessionInterface $session, RedirectionInterface $redirection,
                        AuthenticationSettings $settings)
  {
    $this->session        = $session;
    $this->kernelSettings = $kernelSettings;
    $this->redirection    = $redirection;
    $this->settings       = $settings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->redirection->setRequest ($request);

    // LOG OUT

    if ($request->getUri () == $this->settings->getLogoutUrl ()) {
      $this->session->logout ();
      return $this->redirection->home ();
    }

    switch ($request->getMethod ()) {
      case 'GET':

        // LOG IN

        if (!$this->session->loggedIn ())
          return $this->redirection->guest ($this->settings->getLoginUrl ());
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
