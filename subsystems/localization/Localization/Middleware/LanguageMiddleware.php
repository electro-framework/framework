<?php
namespace Selenia\Localization\Middleware;

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
    $lang = property ($this->session, 'lang', $this->app->defaultLang);
    $this->locale
      ->setAvailable ($this->app->languages)
      ->setSelectionMode ($this->settings->selectionMode ())
      ->setLocale ($lang);

    return $next();
  }
}
