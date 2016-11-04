<?php
namespace Electro\Profiles;

use Electro\WebApplication\WebBootloader;

class StandardWebProfile extends WebProfile
{
  static public function getBootloaderClass ()
  {
    return WebBootloader::class;
  }

  public function getSubsystems ()
  {
    return [
      'authentication',
      'authorization',
      'caching',
      'configuration',
      'database',
      'debugging',
      'error-handling',
      'content-server',
      'http',
      'localization',
      'mail',
      'navigation',
      'routing',
      'sessions',
      'validation',
      'view-engine',
    ];
  }

}
