<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders an error HTTP response into a format supported by the client.
 */
interface ErrorRendererInterface
{
  /**
   * @param ServerRequestInterface $request
   * @param \Throwable|\Exception  $error
   * @return ResponseInterface
   */
  function render (ServerRequestInterface $request, $error);
}
