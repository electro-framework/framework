<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponseSenderInterface {

  function send (ResponseInterface $response);
}
