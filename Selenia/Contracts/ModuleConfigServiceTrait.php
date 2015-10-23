<?php
namespace Selenia\Contracts;

/**
 * Allows traits to access the module's configuration settings.
 * This is used by console commands.
 */
trait ModuleConfigServiceTrait
{
  /**
   * Gets the specified setting from the module's configuration.
   *
   * This method should be implemented by the class using traits that use this trait.
   * @param string $key
   * @return mixed
   */
  protected abstract function moduleConfig ($key);
}
