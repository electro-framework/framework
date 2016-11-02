<?php
namespace Electro\Authentication\Config;

use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\AssignableInterface;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the authentication subsystem.
 *
 * @method $this|string userModel (string $v = null) The class name of the model that represents the logged-in user
 * @method $this|string loginFormUrl (string $v = null) The relative URL of the login form page
 */
class AuthenticationSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $loginFormUrl = 'login/login';
  private $userModel    = GenericUser::class;
}
