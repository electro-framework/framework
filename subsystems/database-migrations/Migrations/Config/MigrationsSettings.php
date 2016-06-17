<?php
namespace Electro\Migrations\Config;

use Electro\Interfaces\AssignableInterface;
use Electro\Traits\ConfigurationTrait;

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
