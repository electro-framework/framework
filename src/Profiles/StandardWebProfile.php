<?php
namespace Electro\Profiles;

use Electro\WebApplication\WebBootstrapper;

class StandardWebProfile extends WebProfile
{
  static public function getBootstrapperClass ()
  {
    return WebBootstrapper::class;
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
