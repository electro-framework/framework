<?php
namespace Electro\Localization\Middleware;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Localization\Services\Locale;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Post-processes the HTTP response to replace translation keys by the corresponding translation.
 */
class TranslationMiddleware implements RequestHandlerInterface
{
  const FIND_TRANS_KEY = '#\$([A-Z][A-Z0-9_]*)#';
  /**
   * The i18n cached translation table.
   *
   * @var array An array of arrays indexed by language code.
   */
  protected static $translation = [];

  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;
  /**
   * @var LocalizationSettings
   */
  private $settings;

  function __construct (KernelSettings $kernelSettings, Locale $locale, ResponseFactoryInterface $responseFactory,
                        LocalizationSettings $settings)
  {
    $this->kernelSettings  = $kernelSettings;
    $this->locale          = $locale;
    $this->responseFactory = $responseFactory;
    $this->settings        = $settings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    $lang = $this->locale->locale ();
    // The check for app->translation is made here instead of conditionally adding this middleware
    // because loaded modules can change this setting.
    if (!$this->settings->translation || !$lang)
      return $response;

    if (!isset(self::$translation[$lang])) {
      // Load and merge all translation files now.

      self::$translation[$lang] = [];

      $trans   =& self::$translation[$lang];
      $folders = $this->settings->languageFolders;
      foreach ($folders as $folder) {
        $path     = "$folder/$lang.ini";
        $newTrans = fileExists ($path) ? parse_ini_file ($path) : null;
        if ($newTrans)
          $trans = array_merge ($trans, $newTrans);
      }
      if (!$trans) {
        $paths = array_map (function ($path) { return "<li>" . ErrorConsole::shortFileName ($path); }, $folders);
        throw new ConfigException("A translation file for language <b>$lang</b> was not found.
<p>Search paths:
<ul>" . implode ('', $paths) . "</ul>", FlashType::ERROR);
      }
    }
    $out = preg_replace_callback (self::FIND_TRANS_KEY, function ($args) use ($lang) {
      $a = $args[1];
      return empty(self::$translation[$lang][$a]) ? '$' . $a
        : preg_replace ('#\r?\n#', '<br>', self::$translation[$lang][$a]);
    }, $response->getBody ());

    return $response->withBody ($this->responseFactory->makeBodyStream ($out));
  }
}
