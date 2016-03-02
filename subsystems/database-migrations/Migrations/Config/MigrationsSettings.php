<?php
namespace Selenia\Migrations\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the database migrations module.
 *
 * @method $this|string migrationsPath (string $v = null) The module-relative path of the migrations folder
 * @method $this|string seedsPath (string $v = null) The module-relative path of the seeds folder
 */
class MigrationsSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $migrationsPath = 'database/migrations';
  private $seedsPath = 'database/seeds';
}
