<?php
namespace Selene\Contracts;

/**
 * Allows traits to access the module's configuration settings.
 */
trait ModuleConfigServiceTrait
{
  /**
   * Gets the specified setting from the module's configuration.
   * @param string $key
   * @return mixed
   */
  protected abstract function moduleConfig ($key);
}
