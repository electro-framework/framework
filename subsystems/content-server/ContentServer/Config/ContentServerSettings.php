<?php
namespace Electro\ContentServer\Config;

use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Content Server subsystem.
 *
 * @method $this|string fileArchivePath (string $v = null)
 * @method $this|string fileBaseUrl (string $v = null)
 * @method $this|string imagesCachePath (string $v = null)
 * @method $this|int originalImageMaxSize (int $v = null) Maximum width and / or height for uploaded images.
 *                                                        Images exceeding this dimensions are resized to fit them.
 * @method $this|int originalImageQuality (int $v = null) JPEG compression factor for resampled uploaded images.
 */
class ContentServerSettings
{
  use ConfigurationTrait;

  /**
   * @var string
   */
  private $fileArchivePath = 'private/storage/files';
  /**
   * @var string
   */
  private $fileBaseUrl = 'files';
  /**
   * @var string
   */
  private $imagesCachePath = 'private/storage/cache/images';
  /**
   * @var int
   */
  private $originalImageMaxSize = 1024;
  /**
   * @var int
   */
  private $originalImageQuality = 95;

}
