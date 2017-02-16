<?php

namespace Electro\ContentRepository\Config;

/**
 * Configuration settings for the Content Repository subsystem.
 */
class ContentRepositorySettings
{
  /**
   * Filesystem driver to use. Currently it supports 'local' and 'S3'
   *
   * @var string
   */
  public $driver;
  /**
   * @var string
   */
  public $fileArchivePath = 'private/storage/files';
  /**
   * @var string
   */
  public $fileBaseUrl = 'files';
  /**
   * @var int Maximum image size squared, in pixels; calculated as width * height. Defaults to 1280x1024
   */
  public $imageMaxSize = 1280 * 1024;
  /**
   * @var string
   */
  public $imagesCachePath = 'private/storage/cache/images';
  /**
   * @var int Currently not used.
   */
  public $originalImageQuality = 95;

  public function __construct ()
  {
    $this->driver = env ('CONTENT_REPOSITORY_DRIVER', 'local');
  }

}
