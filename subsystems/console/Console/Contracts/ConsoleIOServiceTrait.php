<?php
namespace Selenia\Contracts;

use Selenia\TaskRunner\ConsoleIO;

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
