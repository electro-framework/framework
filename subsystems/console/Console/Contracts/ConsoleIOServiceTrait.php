<?php
namespace Selenia\Console\Contracts;

use Selenia\Console\TaskRunner\ConsoleIO;

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
