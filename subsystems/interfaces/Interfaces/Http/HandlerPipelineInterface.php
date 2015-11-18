<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HandlerPipelineInterface extends RequestHandlerInterface
{
  /**
   * @param string|callable|RequestHandlerInterface $handler
   * @return $this
   */
  function add ($handler);

  /**
   * @param boolean                                 $condition
   * @param string|callable|RequestHandlerInterface $handler
   * @return $this
   */
  function addIf ($condition, $handler);

  /**
   * @return ServerRequestInterface
   */
  function getCurrentRequest ();

  /**
   * @return ResponseInterface
   */
  function getCurrentResponse ();

}
