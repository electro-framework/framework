<?php
namespace Selenia\Http;

use Selenia\Interfaces\ResponseMakerInterface;
use Zend\Diactoros\Response;

class ResponseMaker implements ResponseMakerInterface
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

  function makeStream ($stream = 'php://memory', $status = 200, array $headers = [])
  {
    return new Response($stream, $status, $headers);
  }

  function redirectionTo ($url, $code = 302)
  {
    return $this->make ($code, '', '', ['Location' => $url]);
  }

}
