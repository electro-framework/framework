<?php
namespace Electro\Profiles;

use Electro\ConsoleApplication\ConsoleBootloader;
use Electro\DependencyInjection\Injector;
use Electro\Interfaces\ProfileInterface;
use Electro\Kernel\Services\Kernel;

/**
 * A configuration profile tailored for console-based applications.
 *
 * <p>When testing `$profile instanceof ConsoleProfile`, you can check if a module is being used on a terminal-based
 * application or not, irrespective of the concrete profile being used, as every console profile should inherit from
 * this base class.
 */
class ConsoleProfile implements ProfileInterface
{
  public function getBootloaderClass ()
  {
    return ConsoleBootloader::class;
  }

  public function getExcludedModules ()
  {
    return [];
  }

  public function getInjector ()
  {
    return new Injector;
  }

  public function getKernelClass ()
  {
    return Kernel::class;
  }

  public function getName ()
  {
    return str_segmentsLast (static::class, '\\');
  }

  public function getSubsystems ()
  {
    return [
      'subsystems/configuration',
      'subsystems/database',
      'subsystems/localization',
      'subsystems/mail',
      'subsystems/tasks',
      'subsystems/validation',
    ];
  }

}
