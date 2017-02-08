<?php

namespace Electro\Http\Services;

use Electro\Interfaces\Http\ResponseFactoryInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;

class ResponseFactory implements ResponseFactoryInterface
{
  function make ($status = 200, $content = '', $contentType = '', array $headers = [])
  {
    if ($contentType)
      $headers['Content-Type'] = $contentType;
    $response = new Response ($status, $headers, 'php://memory');
    if (isset ($content) && $content !== '')
      $response->getBody ()->write ($content);
    return $response;
  }

  function makeBodyStream ($content = '', $stream = 'php://memory')
  {
    /** @noinspection PhpParamsInspection */
    $s = new Stream (is_string ($stream) ? fopen ($stream, 'wb+') : $stream);
    if (exists ($content)) $s->write ($content);
    return $s;
  }

  function makeFromStream ($stream = 'php://memory', $status = 200, array $headers = [])
  {
    return new Response($status, $headers, is_string ($stream) ? fopen ($stream, 'wb+') : $stream);
  }

  function makeHtmlResponse ($content = '')
  {
    return $this->make (200, $content, 'text/html');
  }

  function makeJsonResponse ($data)
  {
    return $this->make (200, json_encode ($data), 'application/json');
  }

}
