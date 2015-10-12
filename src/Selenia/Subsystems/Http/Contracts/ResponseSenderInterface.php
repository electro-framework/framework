<?php
namespace Selenia\Subsystems\Http\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ResponseSenderInterface {

  function send (ResponseInterface $response);
}
