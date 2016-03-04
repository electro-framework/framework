<?php
namespace Selenia\FileServer\Services;

use League\Glide\Urls\UrlBuilder;
use Selenia\Interfaces\ContentRepositoryInterface;

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
