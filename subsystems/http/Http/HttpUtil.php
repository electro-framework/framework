<?php
namespace Selenia\Http;

use Psr\Http\Message\ServerRequestInterface;

class HttpUtil
{
  /**
   * Checks if the HTTP client accepts the given content type.
   * @param ServerRequestInterface $request
   * @param string $contentType Ex: <kbd>'text/html'</kbd>
   * @return boolean
   */
  static function clientAccepts (ServerRequestInterface $request, $contentType)
  {
    return strpos ($request->getHeaderLine ('Accept'), $contentType) !== false;
  }

}
