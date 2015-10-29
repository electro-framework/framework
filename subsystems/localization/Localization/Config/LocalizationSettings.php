<?php
namespace Selenia\Localization\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Localization module.
 *
 * @method $this selectionMode (string $mode) How to automatically set the current locale.  Either 'session' or 'url'.
 * @method $this timeZone (string $name) If set, sets the currently active timezone. Ex: 'Europe/Lisbon'
 * @method string getSelectionMode ()
 * @method string|null getTimeZone ()
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
