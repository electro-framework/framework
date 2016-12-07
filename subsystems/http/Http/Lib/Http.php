<?php
namespace Electro\Http\Lib;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Utility methods for working with HTTP messages.
 */
class Http
{
  const BAD_REQUEST        = 400;
  const FORBIDDEN          = 403;
  const NOT_FOUND          = 404;
  const PERMANENT_REDIRECT = 308;
  const SEE_OTHER          = 303;
  const TEMPORARY_REDIRECT = 307;
  const UNAUTHORIZED       = 401;

  /**
   * Checks if the HTTP client accepts the given content type.
   *
   * @param ServerRequestInterface $request
   * @param string                 $contentType Ex: <kbd>'text/html'</kbd>
   * @return boolean
   */
  static function clientAccepts (ServerRequestInterface $request, $contentType)
  {
    return strpos ($request->getHeaderLine ('Accept'), $contentType) !== false;
  }

  /**
   * Utility method for retrieving the value of a form field submitted via a `application/x-www-form-urlencoded` or a
   * `multipart/form-data` POST request.
   *
   * @param ServerRequestInterface $request
   * @param string                 $name The field name.
   * @param mixed                  $def  [optional] A default value.
   * @return mixed
   */
  static function field (ServerRequestInterface $request, $name, $def = null)
  {
    return get ($request->getParsedBody (), $name, $def);
  }

  /**
   * Returns a map of routing parameters extracted from the request attributes (which mast have been set previously by
   * a router).
   *
   * @param ServerRequestInterface $request
   * @return array A map of name => value of all routing parameters set on the request.
   */
  static function getRouteParameters (ServerRequestInterface $request)
  {
    return mapAndFilter ($request->getAttributes (), function ($v, &$k) {
      if ($k && $k[0] == '@') {
        $k = substr ($k, 1);
        return $v;
      }
      return null;
    });
  }

  /**
   * Decodes a JSON response.
   *
   * @param ResponseInterface $response
   * @param bool              $assoc [optional] Return an associative array?
   * @return mixed
   */
  static function jsonFromResponse (ResponseInterface $response, $assoc = false)
  {
    if ($response->getHeaderLine ('Content-Type') != 'application/json')
      throw new \RuntimeException ("HTTP response is not of type JSON");
    return json_decode ($response->getBody (), $assoc);
  }

  /**
   * Creates a JSON-encoded response from the given data.
   *
   * @param ResponseInterface $response
   * @param mixed             $data
   * @return ResponseInterface
   */
  static function jsonResponse (ResponseInterface $response, $data)
  {
    return self::response ($response, json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      'application/json');
  }

  /**
   * Simplifies setting response object properties to return a simple HTTP redirection response.
   *
   * @param ResponseInterface $response    An existing, pristine, response object.
   * @param string            $url         The target URL.
   * @param int               $status      HTTP status code.
   *                                       <p>Valid redirection values should be:
   *                                       <p>303 - See Other
   *                                       <p>307 - Temporary Redirect
   *                                       <p>308 - Permanent Redirect
   * @return ResponseInterface A new response object.
   */
  static function redirect (ResponseInterface $response, $url, $status = 307)
  {
    return $response->withStatus ($status)->withHeader ('Location', $url);
  }

  /**
   * Simplifies setting response object properties to return a simple HTTP response.
   *
   * @param ResponseInterface $response    An existing, pristine, response object.
   * @param string            $body        Am optional HTML body content.
   * @param string            $contentType Defaults to 'text/html'.
   * @param int               $status      HTTP status code.
   * @return ResponseInterface A new response object.
   */
  static function response (ResponseInterface $response, $body = '', $contentType = 'text/html', $status = 200)
  {
    if ($body)
      $response->getBody ()->write ($body);
    return $response->withStatus ($status)->withHeader ('Content-Type', $contentType);
  }

}
