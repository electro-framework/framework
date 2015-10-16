<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface {

  /**
   * @param ServerRequestInterface  $request
   * @param ResponseInterface $response
   * @param callable          $next A function with arguments (ServerRequestInterface $request, ResponseInterface $response).
   * @return ResponseInterface
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next);
}
