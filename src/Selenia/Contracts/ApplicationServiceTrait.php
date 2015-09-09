<?php
namespace Selenia\Contracts;
use Selenia\Application;

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
