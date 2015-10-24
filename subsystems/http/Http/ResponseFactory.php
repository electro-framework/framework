<?php
namespace Selenia\Http;

use Selenia\Interfaces\ResponseFactoryInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class ResponseFactory implements ResponseFactoryInterface
{

  function make ($status = 200, $content = '', $contentType = 'text/html', array $headers = [])
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
    if ($content) $s->write ($content);
    return $s;
  }

  function makeStream ($stream = 'php://memory', $status = 200, array $headers = [])
  {
    return new Response($stream, $status, $headers);
  }

}
