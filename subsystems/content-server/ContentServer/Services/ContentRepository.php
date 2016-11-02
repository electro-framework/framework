<?php
namespace Electro\ContentServer\Services;

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

  public function getImageUrl ($path, array $params = [])
  {
    if (exists ($path))
      return rtrim (substr ($this->urlBuilder->getUrl ($path, $params), 1), '?');
    return '';
  }
}
