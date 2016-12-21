<?php
namespace Electro\Localization\Middleware;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Localization\Services\Locale;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class LanguageMiddleware implements RequestHandlerInterface
{
  /**
   * @var DebugSettings
   */
  private $debugSettings;
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var SessionInterface
   */
  private $session;
  /**
   * @var LocalizationSettings
   */
  private $settings;

  /**
   * LanguageMiddleware constructor.
   *
   * @param SessionInterface     $session
   * @param Locale               $locale
   * @param LocalizationSettings $settings
   * @param bool $webConsole
   */
  function __construct (SessionInterface $session, Locale $locale, LocalizationSettings $settings,
                           DebugSettings $debugSettings)
  {
    $this->session  = $session;
    $this->locale   = $locale;
    $this->settings = $settings;
    $this->debugSettings = $debugSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $mode = $this->settings->selectionMode ();
    $this->locale->selectionMode ($mode);
    if ($mode == 'session') {
      $lang = $this->session->getLang () ?: $this->locale->defaultLang ();
      $this->locale->locale ($lang);
      $this->session->setLang ($lang);
    }

    if ($this->debugSettings->logConfig)
      DebugConsole::logger ('config')->inspect ($this->locale);
    return $next();
  }
}
