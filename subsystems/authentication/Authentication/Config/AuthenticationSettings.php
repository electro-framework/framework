<?php
namespace Electro\Authentication\Config;

use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\AssignableInterface;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the authentication module.
 *
 * @method $this|string userModel (string $v = null) The class name of the model that represents the logged-in user
 */
class AuthenticationSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $userModel = GenericUser::class;
}
