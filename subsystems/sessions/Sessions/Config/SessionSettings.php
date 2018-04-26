<?php
namespace Electro\Sessions\Config;

class SessionSettings
{
  /**
   * The application name.
   * This should be composed only of alphanumeric characters. It is used as the session name.
   *
   * @var string
   */
  public $sessionName = 'electro';
  /**
   * The name of remember me token
   *
   * @var string
   */
  public $rememberMeTokenName = "rememberMe";
}
