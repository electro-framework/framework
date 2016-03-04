<?php
namespace Selenia\Sessions\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;
use Selenia\Http\Lib\Http;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\SessionInterface;

/**
 *
 */
class SessionMiddleware implements RequestHandlerInterface
{
  /** @var Application */
  private $app;
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
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

    $flashMessage = $session->getFlashMessage ();
    if ($flashMessage)
      $request = $this->renderFlashMessage ($request, $flashMessage['type'], $flashMessage['message']);

    // Run the next middleware, catching any flash message exceptions.

    try {
      return $next($request);
    }
    catch (FlashMessageException $flash) {
      $session->flashMessage ($flash->getMessage (), $flash->getCode (), $flash->getTitle ());

      $post = $request->getParsedBody ();
      if (is_array ($post))
        $session->flashInput ($post);

      return $this->redirection->refresh ();
    }
  }

  /**
   * Sets the `statusMessage` property on the shared view model to a rendered HTML status message.
   * <p>Override to define a different template or rendering mechanism.
   *
   * @param ServerRequestInterface $request
   * @param int                    $status
   * @param string                 $message
   * @return ServerRequestInterface Mutated request.
   */
  protected function renderFlashMessage (ServerRequestInterface $request, $status, $message)
  {
    $viewModel = Http::getViewModel ($request);
    if (!is_null ($status)) {
      switch ($status) {
        case FlashType::FATAL:
          @ob_clean ();
          echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></head><body><pre>' .
               $message .
               '</pre></body></html>';
          exit;
        case FlashType::ERROR:
          $msg = '<div id="status" class="alert alert-danger" role="alert"><div>' . $message . '</div></div>';
          break;
        case FlashType::WARNING:
          $msg = '<div id="status" class="alert alert-warning" role="alert"><div>' . $message . '</div></div>';
          break;
        default:
          $msg = '<div id="status" class="alert alert-info" role="alert"><div>' . $message . '</div></div>';
      }
      $viewModel['statusMessage'] = $msg;
      return Http::updateViewModel ($request, $viewModel);
    }
    return $request;
  }

}
