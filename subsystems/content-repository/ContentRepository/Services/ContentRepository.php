<?php

namespace Electro\ContentRepository\Services;

use Electro\ContentRepository\Lib\FileUtil;
use Electro\Interfaces\ContentRepositoryInterface;
use League\Glide\Server;
use League\Glide\Urls\UrlBuilder;
use Psr\Http\Message\UploadedFileInterface;

class ContentRepository implements ContentRepositoryInterface
{
  /** @var Server */
  private $server;
  /** @var UrlBuilder */
  private $urlBuilder;

  public function __construct (Server $server, UrlBuilder $urlBuilder)
  {
    $this->urlBuilder = $urlBuilder;
    $this->server     = $server;
  }

  public function deleteFile ($virtualPath)
  {
    $this->server->getSource ()->delete ($virtualPath);
    $this->server->deleteCache ($virtualPath);
  }

  public function getFileUrl ($path)
  {
    if (exists ($path))
      // Note: urlBuilder always returns a leading slash, we must remove it.
      return rtrim (substr ($this->urlBuilder->getUrl ($path), 1), '?');
    return '';
  }

  public function getImageUrl ($path, array $params = [])
  {
    if (exists ($path)) {
      // Note: urlBuilder always returns a leading slash, we must remove it.
      return rtrim (substr ($this->urlBuilder->getUrl ($path, $params), 1), '?');
    }
    return '';
  }

  public function saveFile ($repoPath, $file, $mime)
  {
    if (is_string ($file))
      $file = fopen ($file, 'rb');
    $this->server->getSource ()->writeStream ($repoPath, $file, ['mimetype' => $mime]);
    fclose ($file);
  }

  public function saveUploadedFile ($virtualPath, UploadedFileInterface $file)
  {
    if (exists ($virtualPath))
      $this->saveFile ($virtualPath, $file->getStream ()->detach (), FileUtil::getUploadedFileMimeType ($file));
    else throw new \InvalidArgumentException("The file's virtual path is not defined");
  }

}
