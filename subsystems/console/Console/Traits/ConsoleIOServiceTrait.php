<?php
namespace Selenia\Console\Traits;

use Selenia\Console\Services\ConsoleIO;

/**
 * Allows traits to access the ConsoleIO service.
 */
trait ConsoleIOServiceTrait
{
  /**
   * @return ConsoleIO
   */
  protected abstract function io ();
}
