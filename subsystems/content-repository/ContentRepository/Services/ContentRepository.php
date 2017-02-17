<?php

namespace Electro\ContentRepository\Services;

use Electro\ContentRepository\Lib\FileUtil;
use Electro\Interfaces\ContentRepositoryInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\UnreadableFileException;
use League\Glide\Server;
use League\Glide\Urls\UrlBuilder;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

class ContentRepository implements ContentRepositoryInterface
{
  /** @var LoggerInterface */
  private $logger;
  /** @var Server */
  private $server;
  /** @var UrlBuilder */
  private $urlBuilder;

  public function __construct (Server $server, UrlBuilder $urlBuilder, LoggerInterface $logger)
  {
    $this->urlBuilder = $urlBuilder;
    $this->server     = $server;
    $this->logger     = $logger;
  }

  public function deleteFile ($virtualPath)
  {
    $source = $this->server->getSource ();
    $cache  = $this->server->getCache ();

    // Delete the source file.
    try {
      $source->delete ($virtualPath);
    }
    catch (FileNotFoundException $e) {
      $this->logger->warning ($e->getMessage ());
    }
    catch (UnreadableFileException $e) {
      $this->logger->warning ($e->getMessage ());
    }

    // Delete the related cached files.
    try {
      $this->server->deleteCache ($virtualPath);
    }
    catch (FileNotFoundException $e) {
      $this->logger->warning ($e->getMessage ());
    }
    catch (UnreadableFileException $e) {
      $this->logger->warning ($e->getMessage ());
    }

    // Cleanup empty directories

    $dir = $virtualPath;
    while ($dir = dirnameEx ($dir)) {
      $list = $source->listContents ($dir);
      if ($list && (count ($list) > 1 || $list[0]['basename'][0] != '.')) // Ignore single hidden file (ex: .DS_Store)
        break;
      else $source->deleteDir ($dir);
    }

    $dir = $virtualPath;
    while ($dir = dirnameEx ($dir)) {
      $list = $cache->listContents ($dir);
      if ($list && (count ($list) > 1 || $list[0]['basename'][0] != '.')) // Ignore single hidden file (ex: .DS_Store)
        break;
      else $cache->deleteDir ($dir);
    }
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
