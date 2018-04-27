<?php
namespace Electro\Authentication\Config;

use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\AssignableInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the authentication subsystem.
 *
 * @method $this|string userModel (string $v = null) The class name of the model that represents the logged-in user
 * @method $this|string loginFormUrl (string $v = null) The relative URL of the login form page
 * @method $this|string logoutUrl (string $v = null) The relative URL of the logout route
 * @method $this|string urlPrefix (string $v = null) Relative URL that prefixes all URLs to the login pages
 */
class AuthenticationSettings implements AssignableInterface
{
  use ConfigurationTrait;

  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  private $loginFormUrl = 'login';
  private $logoutUrl    = 'logout';
  private $urlPrefix    = 'login';
  private $userModel    = GenericUser::class; // Accessed via userModel()

  public function __construct (KernelSettings $kernelSettings)
  {
    $this->kernelSettings = $kernelSettings;
  }

  function getLoginUrl ()
  {
    return "{$this->kernelSettings->baseUrl}/$this->urlPrefix/$this->loginFormUrl";
  }

  function getLogoutUrl ()
  {
    return "{$this->kernelSettings->baseUrl}/$this->urlPrefix/$this->logoutUrl";
  }

}
