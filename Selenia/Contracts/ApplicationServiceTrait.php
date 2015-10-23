<?php
namespace Selenia\Contracts;
use Selenia\Application;

/**
 * Allows traits to access the Application service.
 * This is used by console commands.
 */
trait ApplicationServiceTrait
{
  /**
   * This method should be implemented by the class using traits that use this trait.
   * @return Application
   */
  protected abstract function app();
}
