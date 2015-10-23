<?php
namespace Selenia\Localization;

use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Localization module.
 *
 * @method $this selectionMode (string $mode) How to automatically set the current locale.  Either 'session' or 'url'.
 * @method string getSelectionMode ()
 */
class LocalizationConfig
{
  use ConfigurationTrait

  /**
   * @var string
   */
  private $selectionMode;

}
