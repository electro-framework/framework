<?php
namespace Selenia\Migrations\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the database migrations module.
 *
 * @method $this|string migrationsPath (string $v = null) The module-relative path of the migrations folder
 */
class MigrationsSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $migrationsPath = 'database/migrations';
}
