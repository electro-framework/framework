<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface {

  /**
   * @param ServerRequestInterface  $request
   * @param ResponseInterface $response
   * @param callable          $next A function with arguments (ServerRequestInterface $request, ResponseInterface $response).
   * @return ResponseInterface
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next);
}