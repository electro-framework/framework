<?php
namespace Selenia\Http\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ResponseSenderInterface {

  function send (ResponseInterface $response);
}
