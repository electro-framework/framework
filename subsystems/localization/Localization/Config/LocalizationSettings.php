<?php
namespace Selenia\Localization\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Localization module.
 *
 * @method $this|string selectionMode (string $mode = null) How to automatically set the current locale: `session|url`.
 * @method $this|string timeZone (string $name = null) If set, sets the currently active timezone. Ex: 'Europe/Lisbon'
 */
class LocalizationSettings implements AssignableInterface
{
  use ConfigurationTrait;

  /**
   * @var string
   */
  private $selectionMode = 'session';
  /**
   * @var string|null
   */
  private $timeZone = null;

}
