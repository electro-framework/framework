<?php
namespace Electro\Core\Profiles;

use Electro\Interfaces\ProfileInterface;

class ConsoleProfile implements ProfileInterface
{
  public function getExcludedModules ()
  {
    return [];
  }

  public function getSubsystems ()
  {
    return [
      'configuration',
      'database',
      'localization',
      'mail',
      'tasks',
      'validation',
    ];
  }

  public function getName ()
  {
    return str_segmentsLast (static::class, '\\');
  }

}
