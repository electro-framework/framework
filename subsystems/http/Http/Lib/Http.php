<?php
namespace Selenia\Http\Lib;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Utility methods for working with HTTP messages.
 */
class Http
{
  const ACTION_FIELD = 'selenia-action';

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
   * Simplifies setting response object properties to return a simple HTTP response.
   *
   * @param ResponseInterface $response    An existing, pristine, response object.
   * @param int               $status      HTTP status code.
   * @param string            $reason      The HTTP reason phrase.
   * @param string            $body        Am optional HTML body content.
   * @param string            $contentType Defaults to 'text/html'.
   * @return ResponseInterface The same response object that was supplied.
   */
  static function send (ResponseInterface $response, $status = 200, $reason = '', $body = '', $contentType =
  'text/html')
  {
    if ($body)
      $response->getBody ()->write ($body);
    return $response->withStatus ($status, $reason)->withHeader ('Content-Type', $contentType);
  }

}
