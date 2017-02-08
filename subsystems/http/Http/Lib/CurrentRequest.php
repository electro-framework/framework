<?php

namespace Electro\Http\Lib;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Wraps a ServerRequestInterface instance that can be replaced at any time.
 */
class CurrentRequest implements CurrentRequestInterface
{
  /** @var ServerRequestInterface */
  private $instance = null;

  public function getBody ()
  {
    return $this->instance->getBody ();
  }

  public function getHeader ($name)
  {
    return $this->instance->getHeader ($name);
  }

  public function getHeaderLine ($name)
  {
    return $this->instance->getHeaderLine ($name);
  }

  public function getHeaders ()
  {
    return $this->instance->getHeaders ();
  }

  function getInstance ()
  {
    return $this->instance;
  }

  function setInstance (ServerRequestInterface $req)
  {
    $this->instance = $req;
  }

  public function getMethod ()
  {
    return $this->instance->getMethod ();
  }

  public function getProtocolVersion ()
  {
    return $this->instance->getProtocolVersion ();
  }

  public function getRequestTarget ()
  {
    return $this->instance->getRequestTarget ();
  }

  public function getUri ()
  {
    return $this->instance->getUri ();
  }

  public function hasHeader ($name)
  {
    return $this->instance->hasHeader ($name);
  }

  public function withAddedHeader ($name, $value)
  {
    return $this->instance->withAddedHeader ($name, $value);
  }

  public function withBody (StreamInterface $body)
  {
    return $this->instance->withBody ($body);
  }

  public function withHeader ($name, $value)
  {
    return $this->instance->withHeader ($name, $value);
  }

  public function withMethod ($method)
  {
    return $this->instance->withMethod ($method);
  }

  public function withProtocolVersion ($version)
  {
    return $this->instance->withProtocolVersion ($version);
  }

  public function withRequestTarget ($requestTarget)
  {
    return $this->instance->withRequestTarget ($requestTarget);
  }

  public function withUri (UriInterface $uri, $preserveHost = false)
  {
    return $this->instance->withUri ($uri, $preserveHost);
  }

  public function withoutHeader ($name)
  {
    return $this->instance->withoutHeader ($name);
  }

}
