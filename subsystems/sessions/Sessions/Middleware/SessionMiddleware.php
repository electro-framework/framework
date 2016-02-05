<?php
namespace Selenia\Sessions\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\SessionInterface;

/**
 *
 */
class SessionMiddleware implements RequestHandlerInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var RedirectionInterface
   */
  private $redirection;
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (SessionInterface $session, Application $app, RedirectionInterface $redirection)
  {
    $this->app         = $app;
    $this->redirection = $redirection;
    $this->session     = $session;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app     = $this->app;
    $session = $this->session;
    $this->redirection->setRequest ($request);

    // Start the sessions engine.

    session_name ($app->name);
    $name = session_name ();
    session_start ();

    // Load the saved session (if any).

    /** @var AssignableInterface $savedSession */
    if ($savedSession = get ($_SESSION, '#data'))
      $this->session->_assign ($savedSession->_export ());

    // (Re)initialize some session settings.

    $session->name = $name;

    // Setup current session to be saved on shutdown.

    $_SESSION['#data'] = $session;

    // Run the next middleware, catching any flash message exceptions.

    try {
      return $next();
    }
    catch (FlashMessageException $flash) {
      $session->flashMessage ($flash->getMessage (), $flash->getCode (), $flash->getTitle ());

      $post = $request->getParsedBody ();
      if (is_array ($post))
        $session->flashInput ($post);

      return $this->redirection->refresh ();
    }
  }

}
