<?php
namespace Selenia\Sessions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Http\Redirection;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\SessionInterface;

/**
 *
 */
class SessionMiddleware implements MiddlewareInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var Redirection
   */
  private $redirection;

  function __construct (Application $app, InjectorInterface $injector, Redirection $redirection)
  {
    $this->app         = $app;
    $this->injector    = $injector;
    $this->redirection = $redirection;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @global SessionInterface $session */
    global $session;

    $this->injector->delegate ('Selenia\Interfaces\SessionInterface', function () {
      global $session;
      if ($session) return $session;

      $app = $this->app;
      if (!$app->globalSessions)
        session_name ($app->name);
      $name = session_name ();
      session_start ();
      /** @var Session $session */
      $session       = get ($_SESSION, '#data') ?: new Session;
      $session->name = $name;
      if (is_null ($session->getLang ()))
        $session->setLang ($app->defaultLang);
      return $_SESSION['#data'] = $session;
    });

    try {
      return $next();
    } catch (FlashMessageException $flash) {
      $session->flashMessage ($flash->getMessage (), $flash->getCode (), $flash->title);
      return $this->redirection->refresh ();
    }
  }
}
