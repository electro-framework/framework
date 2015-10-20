<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

interface ResponseMakerInterface
{

  /**
   * Creates a new HTTP response object, compatible with ResponseInterface.
   * @param int    $status
   * @param string $content
   * @param string $contentType
   * @param array  $headers
   * @return ResponseInterface
   */
  function make ($status = 200, $content = '', $contentType = 'text/html', array $headers = []);

  /**
   * Creates a new HTTP response object, compatible with ResponseInterface.
   * @param string|resource|StreamInterface $stream  Stream identifier and/or actual stream resource
   * @param int                             $status  Status code for the response, if any.
   * @param array                           $headers Headers for the response, if any.
   * @throws \InvalidArgumentException on any invalid element.
   * @return ResponseInterface
   */
  function makeStream ($stream = 'php://memory', $status = 200, array $headers = []);

  /**
   * Creates a new redirection HTTP response object, compatible with ResponseInterface.
   * @param string $url  Either a full or relative URL.
   * @param int    $code A 3xx HTTP status code.
   * @return ResponseInterface
   */
  function redirectionTo ($url, $code = 302);
}
