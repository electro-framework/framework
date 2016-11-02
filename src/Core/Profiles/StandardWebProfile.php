<?php
namespace Electro\Core\Profiles;

class StandardWebProfile extends WebProfile
{
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
