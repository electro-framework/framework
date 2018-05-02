<?php
namespace Electro\Http\Config;

use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Platform module.
 * @method $this|boolean useCsrfToken (boolean $v = null) Enable the use of CsrfToken middleware?
 */

class HttpSettings
{
  use ConfigurationTrait;

  private $useCsrfToken = true;
}