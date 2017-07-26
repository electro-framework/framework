<?php
namespace Electro\Sessions\Middleware;

use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\AssignableInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\ViewEngine\Services\AssetsService;
use Electro\ViewEngine\Services\BlocksService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
  /** @var \Electro\ViewEngine\Services\AssetsService */
  private $assetsService;
  /** @var BlocksService */
  private $blocksService;
  /** @var KernelSettings */
  private $kernelSettings;
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;

  function __construct (SessionInterface $session, KernelSettings $kernelSettings, RedirectionInterface $redirection,
                        BlocksService $blocksService, AssetsService $assetsService)
  {
    $this->kernelSettings = $kernelSettings;
    $this->redirection    = $redirection;
    $this->session        = $session;
    $this->blocksService  = $blocksService;
    $this->assetsService  = $assetsService;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $settings = $this->kernelSettings;
    $session  = $this->session;
    $this->redirection->setRequest ($request);

    // Start the sessions engine.

    session_name ($settings->name);
    $name = session_name ();
    session_start ();

    // Load the saved session (if any).

    /** @var AssignableInterface $savedSession */
    if (($savedSession = get ($_SESSION, '#data'))
        // make sure it's not an incomplete object loaded from a no longer existing class
        && $savedSession instanceof AssignableInterface
    )
      $this->session->import ($savedSession->export ());

    // (Re)initialize some session settings.
    $session->name = $name;

    // Setup current session to be saved on shutdown.
    $_SESSION['#data'] = $session;

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

}
