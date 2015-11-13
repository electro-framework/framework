<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseSenderInterface {

  function send (ResponseInterface $response);
}
