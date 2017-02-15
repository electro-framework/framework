<?php

namespace Electro\ContentRepository\Services;

use Electro\Interfaces\ContentRepositoryInterface;
use League\Glide\Urls\UrlBuilder;

class ContentRepository implements ContentRepositoryInterface
{
  /** @var UrlBuilder */
  private $urlBuilder;

  public function __construct (UrlBuilder $urlBuilder)
  {
    $this->urlBuilder = $urlBuilder;
  }

  public function getFileUrl ($path)
  {
    if (exists ($path))
      return rtrim ($this->urlBuilder->getUrl ($path), '?');
    return '';
  }

  public function getImageUrl ($path, array $params = [])
  {
    if (exists ($path)) {
      return rtrim ($this->urlBuilder->getUrl ($path, $params), '?');
    }
    return '';
  }

}
