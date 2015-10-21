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
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (SessionInterface $session, Application $app, InjectorInterface $injector,
                        Redirection $redirection)
  {
    $this->app         = $app;
    $this->injector    = $injector;
    $this->redirection = $redirection;
    $this->session     = $session;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app = $this->app;
    $session = $this->session;

    // Start the sessions engine.

    if (!$app->globalSessions)
      session_name ($app->name);
    $name = session_name ();
    session_start ();

    // Load the saved session (if any).

    if ($savedSession = get ($_SESSION, '#data'))
      $this->session->assign ($savedSession->export ());

    // (Re)initialize some session settings.

    $session->name = $name;
    if (is_null ($session->getLang ()))
      $session->setLang ($app->defaultLang);

    // Setup current session to be saved on shutdown.

    $_SESSION['#data'] = $session;

    // Run the next middleware, catching any flash message exceptions.

    try {
      return $next();
    } catch (FlashMessageException $flash) {
      $session->flashMessage ($flash->getMessage (), $flash->getCode (), $flash->title);
      return $this->redirection->refresh ();
    }
  }

}
