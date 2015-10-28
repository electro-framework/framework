<?php
namespace Selenia\Localization\Middleware;
use PhpKit\WebConsole\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Exceptions\FlashType;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\ResponseFactoryInterface;
use Selenia\Localization\Services\Locale;

/**
 * Post-processes the HTTP response to replace translation keys by the corresponding translation.
 */
class TranslationMiddleware implements MiddlewareInterface
{
  const FIND_TRANS_KEY = '#\$([A-Z][A-Z0-9_]*)#';
  /**
   * The i18n cached translation table.
   * @var array An array of arrays indexed by language code.
   */
  protected static $translation = [];

  /**
   * @var Application
   */
  private $app;
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;

  function __construct (Application $app, Locale $locale, ResponseFactoryInterface $responseFactory)
  {
    $this->app             = $app;
    $this->locale          = $locale;
    $this->responseFactory = $responseFactory;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    $lang = $this->locale->name;
    // The check for app->translation is made here instead of conditionally adding this middleware
    // because loaded modules can change this setting.
    if (!$this->app->translation || !$lang)
      return $response;

    if (!isset(self::$translation[$lang])) {
      $found   = false;
      $folders = array_reverse ($this->app->languageFolders);
      foreach ($folders as $folder) {
        $path = "$folder/$lang.ini";
        $z    = file_exists ($path) ? parse_ini_file ($path) : null;
        if (!empty($z)) {
          $found                    = true;
          self::$translation[$lang] = array_merge (get (self::$translation, $lang, []), $z);
        }
      }
      if (!$found) {
        $paths = array_map (function ($path) { return "<li>" . ErrorHandler::shortFileName ($path); }, $folders);
        throw new ConfigException("A translation file for language <b>$lang</b> was not found.<p>Search paths:<ul>" .
                                  implode ('', $paths) . "</ul>", FlashType::FATAL);
      }
    }
    $out = preg_replace_callback (self::FIND_TRANS_KEY, function ($args) use ($lang) {
      $a = $args[1];
      return empty(self::$translation[$lang][$a]) ? '$' . $a
        : preg_replace ('#\r?\n#', '<br>', self::$translation[$lang][$a]);
    }, $response->getBody ());

    return $response->withBody ($this->responseFactory->makeBody ($out));
  }
}
