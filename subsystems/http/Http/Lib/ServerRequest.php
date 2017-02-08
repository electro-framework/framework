<?php

namespace Electro\Http\Lib;

use GuzzleHttp\Psr7\LazyOpenStream;

/**
 * Server-side HTTP request
 *
 * Extends Guzzle's ServerRequest with the following features:
 *
 * - Fix the return value of {@see getParsedBody} so that it is `null` when there is no body or the content type is not
 * supported, as per the PSR-7 specification.
 * - Extend {@see getParsedBody} to deserialize JSON and XML request bodies.
 */
class ServerRequest extends \GuzzleHttp\Psr7\ServerRequest
{
  /**
   * @inheritdoc
   * @note This replaces the inherited method so that it creates an instance of this class instead of the original
   * class.
   *
   * @return static
   */
  public static function fromGlobals ()
  {
    $method   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $headers  = function_exists ('getallheaders') ? getallheaders () : [];
    $uri      = self::getUriFromGlobals ();
    $body     = new LazyOpenStream('php://input', 'r+');
    $protocol = isset ($_SERVER['SERVER_PROTOCOL']) ? str_replace ('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

    $serverRequest = new static ($method, $uri, $headers, $body, $protocol, $_SERVER);

    return $serverRequest
      ->withCookieParams ($_COOKIE)
      ->withQueryParams ($_GET)
      ->withParsedBody ($_POST)
      ->withUploadedFiles (self::normalizeFiles ($_FILES));
  }

  public function getParsedBody ()
  {
    if ($this->getMethod () != 'GET') {
      if ($body = parent::getParsedBody ())
        return $body;
      switch ($this->getHeaderLine ('Content-Type')) {
        case 'application/json':
          return json_decode ($this->getBody ()->getContents ());
        case 'application/xml':
          $xml = new \DOMDocument;
          if (!$xml->loadXML ($this->getBody ()->getContents ()))
            throw new \RuntimeException ("Invalid XML request body");
          return $xml;
        // In case of an empty $_POST, return an empty array.
        case 'application/x-www-form-urlencoded':
        case 'multipart/form-data':
          return $body;
      }
    }
    // No request body or unsupported content type.
    return null;
  }

}
