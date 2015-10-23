<?php
namespace Selenia\Localization;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Sessions\Session;

/**
 *
 */
class LanguageMiddleware implements MiddlewareInterface
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
    /** @var LocalizationConfig $config */
    $config = $this->app->config['selenia/localization'];
    $lang   = property ($this->session, 'lang', $this->app->defaultLang);
    $this->locale
      ->setAvailable ($this->app->languages)
      ->setSelectionMode ($config->getSelectionMode())
      ->setLocale ($lang);

    return $next();
  }
}
