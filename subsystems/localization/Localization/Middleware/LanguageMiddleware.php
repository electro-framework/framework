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

  function __construct (Session $session, Application $app, Locale $locale)
  {
    $this->session = $session;
    $this->app     = $app;
    $this->locale  = $locale;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var LocalizationSettings $config */
    $config = $this->app->config['selenia/localization'];
    $lang   = property ($this->session, 'lang', $this->app->defaultLang);
    $this->locale
      ->setAvailable ($this->app->languages)
      ->setSelectionMode ($config->selectionMode())
      ->setLocale ($lang);

    return $next();
  }
}
