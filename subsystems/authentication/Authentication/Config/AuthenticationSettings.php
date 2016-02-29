<?php
namespace Selenia\Authentication\Config;

use Selenia\Authentication\Lib\GenericUser;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

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
