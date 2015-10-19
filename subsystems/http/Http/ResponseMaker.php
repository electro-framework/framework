<?php
namespace Selenia\Http;

use Selenia\Interfaces\ResponseMakerInterface;
use Zend\Diactoros\Response;

class ResponseMaker implements ResponseMakerInterface
{

  function make ($stream = 'php://memory', $status = 200, array $headers = [])
  {
    return new Response($stream, $status, $headers);
  }

}
