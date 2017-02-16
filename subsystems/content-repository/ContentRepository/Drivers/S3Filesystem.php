<?php

namespace Electro\ContentRepository\Drivers;

use Aws\S3\S3Client;
use Electro\ContentRepository\Config\S3Settings;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

/**
 * A preconfigured Amazon S3 filesystem adapter.
 */
class S3Filesystem extends Filesystem
{
  public function __construct (S3Settings $settings)
  {
    $client = S3Client::factory ([
      'credentials' => [
        'key'    => $settings->key,
        'secret' => $settings->secret,
      ],
      'region'      => $settings->region,
      'version'     => '2006-03-01',
    ]);

    $adapter = new AwsS3Adapter($client, $settings->bucket);
    parent::__construct ($adapter);
  }

}
