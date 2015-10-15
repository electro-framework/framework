<?php
namespace Selenia\Subsystems\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Exceptions\ConfigException;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class LanguageMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
    global $application, $session, $controller;
    if (empty($application->languages))
      return;
    $controller->languages = $application->languages;
    $controller->langInfo  = [];
    foreach ($controller->languages as $langDat) {
      $langDat                           = explode (':', $langDat);
      $controller->langInfo[$langDat[0]] = [
        'value'  => $langDat[0],
        'ISO'    => $langDat[1],
        'label'  => $langDat[2],
        'locale' => explode ('|', $langDat[3]),
      ];
    }
    $controller->lang = firstNonNull ($controller->lang, property ($session, 'lang'), $application->defaultLang);
    if (isset($session)) {
      if ($session->lang != $controller->lang)
        $session->setLang ($controller->lang);
    }
    if (isset($controller->lang)) {
      if (!array_key_exists ($controller->lang, $controller->langInfo)) {
        $controller->lang = $application->defaultLang;
        if (isset($session))
          $session->setLang ($controller->lang);
        $controller->setStatus (Status::ERROR, 'An invalid language was specified.');
      }
      $info = get ($controller->langInfo, $controller->lang);
      if (!isset($info))
        throw new ConfigException("Language <b>$controller->lang</b> is not configured for this application.");
      $locales               = $controller->langInfo[$controller->lang]['locale'];
      $controller->locale    = $locales[0];
      $controller->langISO   = $controller->langInfo[$controller->lang]['ISO'];
      $controller->langLabel = $controller->langInfo[$controller->lang]['label'];
      setlocale (LC_ALL, $locales);
    }
  }
}
