<?php
namespace Electro\Sessions\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Application;
use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\AssignableInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\ViewEngine\Services\AssetsService;
use Electro\ViewEngine\Services\BlocksService;

/**
 *
 */
class SessionMiddleware implements RequestHandlerInterface
{
  private static $FLASH_CLASSES = [
    FlashType::INFO    => 'info',
    FlashType::ERROR   => 'danger',
    FlashType::WARNING => 'warning',
    FlashType::SUCCESS => 'success',
  ];

  /** @var Application */
  private $app;
  /** @var \Electro\ViewEngine\Services\AssetsService */
  private $assetsService;
  /** @var BlocksService */
  private $blocksService;
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;

  function __construct (SessionInterface $session, Application $app, RedirectionInterface $redirection,
                        BlocksService $blocksService, AssetsService $assetsService)
  {
    $this->app           = $app;
    $this->redirection   = $redirection;
    $this->session       = $session;
    $this->blocksService = $blocksService;
    $this->assetsService = $assetsService;
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
      $this->session->import ($savedSession->export ());

    // (Re)initialize some session settings.
    $session->name = $name;

    // Setup current session to be saved on shutdown.
    $_SESSION['#data'] = $session;

    $flashMessage = $session->getFlashMessage ();
    if ($flashMessage)
      $this->renderFlashMessage ($flashMessage['type'], $flashMessage['message']);

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
   * Sets the `statusMessage` content block to a rendered HTML status message.
   * <p>Override to define a different template or rendering mechanism.
   *
   * @param int    $status
   * @param string $message
   */
  protected function renderFlashMessage ($status, $message)
  {
    if (!is_null ($status)) {
      $class = get (self::$FLASH_CLASSES, $status);
      $class = $class ? " alert-$class" : '';
      $msg   = "<div id=status class='alert$class' role=alert><div>$message</div></div>";
      $this->blocksService->getBlock ('statusMessage')->set ($msg);
      $this->assetsService->addInlineScript ('setTimeout(function(){$("#status").fadeOut()},5000)');
    }
  }

}
