<?php
namespace Selenia\Console\Contracts;
use Selenia\Core\Assembly\Services\ModulesRegistry;

/**
 * Allows traits to access the ModulesUtil service.
 * This is used by console commands.
 */
trait ModulesRegistryServiceTrait
{
  /**
   * @return ModulesRegistry
   */
  protected abstract function modulesRegistry ();
}
