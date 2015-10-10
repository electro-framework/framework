<?php
namespace Selenia\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface {

  /**
   * @param RequestInterface  $request
   * @param ResponseInterface $response
   * @param callable          $next A function with arguments (RequestInterface $request, ResponseInterface $response).
   * @return ResponseInterface
   */
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next);
}
