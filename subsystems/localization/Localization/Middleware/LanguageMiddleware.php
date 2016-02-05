<?php
namespace Selenia\Localization\Middleware;

use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Localization\Config\LocalizationSettings;
use Selenia\Localization\Services\Locale;
use Selenia\Sessions\Services\Session;

/**
 *
 */
class LanguageMiddleware implements RequestHandlerInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var Session
   */
  private $session;
  /**
   * @var LocalizationSettings
   */
  private $settings;

  function __construct (Session $session, Application $app, Locale $locale, LocalizationSettings $settings)
  {
    $this->session  = $session;
    $this->app      = $app;
    $this->locale   = $locale;
    $this->settings = $settings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $mode = $this->settings->selectionMode ();
    $this->locale->selectionMode ($mode);
    if ($mode == 'session') {
      $lang = $this->session->getLang () ?: $this->locale->defaultLang ();
      $this->locale->locale ($lang);
    }

    if ($this->app->debugMode)
      DebugConsole::logger ('config')->inspect ($this->locale);
    return $next();
  }
}
