<?php
namespace Selenia\Sessions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\ResponseMakerInterface;

/**
 *
 */
class SessionMiddleware implements MiddlewareInterface
{
  private $app;
  private $injector;
  private $responseMaker;

  function __construct (Application $app, InjectorInterface $injector, ResponseMakerInterface $responseMaker)
  {
    $this->app           = $app;
    $this->injector      = $injector;
    $this->responseMaker = $responseMaker;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    global $session;

    $app = $this->app;
    if (!$app->globalSessions)
      session_name ($app->name);
    $name = session_name ();
    session_start ();
    /** @var Session $session */
    $session       = get ($_SESSION, 'data', new Session);
    $session->name = $name;
    $session->setLang ($app->defaultLang);
    $_SESSION['data'] = $session;

    $this->injector->alias ('Selenia\Interfaces\SessionInterface', get_class ($session))->share ($session);

    try {
      return $next();
    } catch (FlashMessageException $flash) {
      $session->flash ($flash->getMessage (), $flash->getCode (), $flash->title);
      return $this->responseMaker->redirectionTo ($request->getUri ());
    }
  }
}
