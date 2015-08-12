<?php
namespace Selene\Contracts;
use Selene\Application;

/**
 * Allows traits to access the Application service.
 */
trait ApplicationServiceTrait
{
  /**
   * @return Application
   */
  protected abstract function app();
}
