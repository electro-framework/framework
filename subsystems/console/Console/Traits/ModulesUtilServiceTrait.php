<?php
namespace Selenia\Console\Traits;
use Selenia\Console\Lib\ModulesUtil;

/**
 * Allows traits to access the ModulesUtil service.
 * This is used by console commands.
 */
trait ModulesUtilServiceTrait
{
  /**
   * @return ModulesUtil
   */
  protected abstract function modulesUtil ();
}
