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

	public function getAttribute($name, $default = null)
	{
		return $this->instance->getAttribute($name, $default);
	}

	public function getAttributes(): array
	{
		return $this->instance->getAttributes();
	}

	public function getBody(): StreamInterface
	{
		return $this->instance->getBody();
	}

	public function getCookieParams(): array
	{
		return $this->instance->getCookieParams();
	}

	public function getHeader($name): array
	{
		return $this->instance->getHeader($name);
	}

	public function getHeaderLine($name): string
	{
		return $this->instance->getHeaderLine($name);
	}

	public function getHeaders(): array
	{
		return $this->instance->getHeaders();
	}

	function getInstance()
	{
		return $this->instance;
	}

	function setInstance(ServerRequestInterface $req)
	{
		$this->instance = $req;
	}

	public function getMethod(): string
	{
		return $this->instance->getMethod();
	}

	public function getParsedBody()
	{
		return $this->instance->getParsedBody();
	}

	public function getProtocolVersion(): string
	{
		return $this->instance->getProtocolVersion();
	}

	public function getQueryParams(): array
	{
		return $this->instance->getQueryParams();
	}

	public function getRequestTarget(): string
	{
		return $this->instance->getRequestTarget();
	}

	public function getServerParams(): array
	{
		return $this->instance->getServerParams();
	}

	public function getUploadedFiles(): array
	{
		return $this->instance->getUploadedFiles();
	}

	public function getUri(): UriInterface
	{
		return $this->instance->getUri();
	}

	public function hasHeader($name): bool
	{
		return $this->instance->hasHeader($name);
	}

	public function withAddedHeader($name, $value): ServerRequestInterface
	{
		return $this->instance->withAddedHeader($name, $value);
	}

	public function withAttribute($name, $value): ServerRequestInterface
	{
		return $this->instance->withAttribute($name, $value);
	}

	public function withBody(StreamInterface $body): ServerRequestInterface
	{
		return $this->instance->withBody($body);
	}

	public function withCookieParams(array $cookies): ServerRequestInterface
	{
		return $this->instance->withCookieParams($cookies);
	}

	public function withHeader($name, $value): ServerRequestInterface
	{
		return $this->instance->withHeader($name, $value);
	}

	public function withMethod($method): ServerRequestInterface
	{
		return $this->instance->withMethod($method);
	}

	public function withParsedBody($data): ServerRequestInterface
	{
		return $this->instance->withParsedBody($data);
	}

	public function withProtocolVersion($version): ServerRequestInterface
	{
		return $this->instance->withProtocolVersion($version);
	}

	public function withQueryParams(array $query): ServerRequestInterface
	{
		return $this->instance->withQueryParams($query);
	}

	public function withRequestTarget($requestTarget): ServerRequestInterface
	{
		return $this->instance->withRequestTarget($requestTarget);
	}

	public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
	{
		return $this->instance->withUploadedFiles($uploadedFiles);
	}

	public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
	{
		return $this->instance->withUri($uri, $preserveHost);
	}

	public function withoutAttribute($name): ServerRequestInterface
	{
		return $this->instance->withoutAttribute($name);
	}

	public function withoutHeader($name): ServerRequestInterface
	{
		return $this->instance->withoutHeader($name);
	}

}
