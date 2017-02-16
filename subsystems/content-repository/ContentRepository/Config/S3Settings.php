<?php

namespace Electro\ContentRepository\Config;

use Auryn\ConfigException;

class S3Settings
{
  public $bucket;
  public $key    = '';
  public $region = '';
  public $secret = '';

  public function __construct ()
  {
    $this->bucket = env ('S3_BUCKET');
    if (!$this->bucket)
      throw new ConfigException ("You haven't configured an S3 bucket yet.
<p>Please check the <kbd>.env</kbd> or <kbd>.env.example</kbd> files.");
  }

  public function getBaseUrl ()
  {
    return "https://s3.$this->region.amazonaws.com/$this->bucket";
  }

}
