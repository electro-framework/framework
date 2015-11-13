<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareStackInterface extends MiddlewareInterface
{
  /**
   * @param string|callable|MiddlewareInterface $middleware
   * @return $this
   */
  function add ($middleware);

  /**
   * @param boolean                             $condition
   * @param string|callable|MiddlewareInterface $middleware
   * @return $this
   */
  function addIf ($condition, $middleware);

  /**
   * @return ServerRequestInterface
   */
  function getCurrentRequest ();

  /**
   * @return ResponseInterface
   */
  function getCurrentResponse ();

}
