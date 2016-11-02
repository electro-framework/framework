<?php
namespace Electro\Core\Profiles;

use Electro\Interfaces\ProfileInterface;

class WebProfile implements ProfileInterface
{
  public function getExcludedModules ()
  {
    return [];
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

  public function getName ()
  {
    return 'web-profile';
  }

}
