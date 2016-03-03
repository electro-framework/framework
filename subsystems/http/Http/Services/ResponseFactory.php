<?php
namespace Selenia\Http\Services;

use Selenia\Interfaces\Http\ResponseFactoryInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class ResponseFactory implements ResponseFactoryInterface
{
  function make ($status = 200, $content = '', $contentType = null, array $headers = [])
  {
    if ($contentType)
      $headers['Content-Type'] = $contentType;
    $response = new Response('php://memory', $status, $headers);
    if ($content)
      $response->getBody ()->write ($content);
    return $response;
  }

  function makeBody ($content = '', $stream = 'php://memory')
  {
    $s = new Stream($stream, 'wb+');
    if (exists ($content)) $s->write ($content);
    return $s;
  }

  function makeHtmlResponse ($content = '')
  {
    return $this->make (200, $content, 'text/html');
  }

  function makeJsonResponse ($data)
  {
    return $this->make (200, json_encode ($data), 'application/json');
  }

  function makeStream ($stream = 'php://memory', $status = 200, array $headers = [])
  {
    return new Response($stream, $status, $headers);
  }

}
