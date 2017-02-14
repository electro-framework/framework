<?php

namespace Electro\ContentRepository\Config;

/**
 * Configuration settings for the Content Repository subsystem.
 */
class ContentRepositorySettings
{
  /**
   * @var string
   */
  public $fileArchivePath = 'private/storage/files';
  /**
   * @var string
   */
  public $fileBaseUrl = 'files';
  /**
   * @var string
   */
  public $imagesCachePath = 'private/storage/cache/images';
  /**
   * @var int
   */
  public $originalImageMaxSize = 1024;
  /**
   * @var int
   */
  public $originalImageQuality = 95;

}
