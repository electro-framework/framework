<?php
namespace Electro\Profiles;

use Electro\ConsoleApplication\ConsoleBootloader;
use Electro\Interfaces\ProfileInterface;

class ConsoleProfile implements ProfileInterface
{
  static public function getBootloaderClass ()
  {
    return ConsoleBootloader::class;
  }

  public function getExcludedModules ()
  {
    return [];
  }

  public function getName ()
  {
    return str_segmentsLast (static::class, '\\');
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

}
