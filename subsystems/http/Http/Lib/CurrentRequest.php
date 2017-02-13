<?php

namespace Electro\Http\Lib;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class CurrentRequest implements CurrentRequestInterface
{
  /** @var ServerRequestInterface */
  private $instance = null;

  public function getAttribute ($name, $default = null)
  {
    return $this->instance->getAttribute ($name, $default);
  }

  public function getAttributes ()
  {
    return $this->instance->getAttributes ();
  }

  public function getBody ()
  {
    return $this->instance->getBody ();
  }

  public function getCookieParams ()
  {
    return $this->instance->getCookieParams ();
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

  public function getParsedBody ()
  {
    return $this->instance->getParsedBody ();
  }

  public function getProtocolVersion ()
  {
    return $this->instance->getProtocolVersion ();
  }

  public function getQueryParams ()
  {
    return $this->instance->getQueryParams ();
  }

  public function getRequestTarget ()
  {
    return $this->instance->getRequestTarget ();
  }

  public function getServerParams ()
  {
    return $this->instance->getServerParams ();
  }

  public function getUploadedFiles ()
  {
    return $this->instance->getUploadedFiles ();
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

  public function withAttribute ($name, $value)
  {
    return $this->instance->withAttribute ($name, $value);
  }

  public function withBody (StreamInterface $body)
  {
    return $this->instance->withBody ($body);
  }

  public function withCookieParams (array $cookies)
  {
    return $this->instance->withCookieParams ($cookies);
  }

  public function withHeader ($name, $value)
  {
    return $this->instance->withHeader ($name, $value);
  }

  public function withMethod ($method)
  {
    return $this->instance->withMethod ($method);
  }

  public function withParsedBody ($data)
  {
    return $this->instance->withParsedBody ($data);
  }

  public function withProtocolVersion ($version)
  {
    return $this->instance->withProtocolVersion ($version);
  }

  public function withQueryParams (array $query)
  {
    return $this->instance->withQueryParams ($query);
  }

  public function withRequestTarget ($requestTarget)
  {
    return $this->instance->withRequestTarget ($requestTarget);
  }

  public function withUploadedFiles (array $uploadedFiles)
  {
    return $this->instance->withUploadedFiles ($uploadedFiles);
  }

  public function withUri (UriInterface $uri, $preserveHost = false)
  {
    return $this->instance->withUri ($uri, $preserveHost);
  }

  public function withoutAttribute ($name)
  {
    return $this->instance->withoutAttribute ($name);
  }

  public function withoutHeader ($name)
  {
    return $this->instance->withoutHeader ($name);
  }
}
