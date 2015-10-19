<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

interface ResponseMakerInterface {

  /**
   * Creates a new HTTP response object, compatible with ResponseInterface.
   * @param string|resource|StreamInterface $stream Stream identifier and/or actual stream resource
   * @param int $status Status code for the response, if any.
   * @param array $headers Headers for the response, if any.
   * @throws \InvalidArgumentException on any invalid element.
   * @return ResponseInterface
   */
  function make ($stream = 'php://memory', $status = 200, array $headers = []);
}
