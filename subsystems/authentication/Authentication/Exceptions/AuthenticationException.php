<?php
namespace Selenia\Authentication\Exceptions;

use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;

class AuthenticationException extends FlashMessageException
{
  const DISABLED       = 4;
  const MISSING_INFO   = 1;
  const UNKNOWN_USER   = 2;
  const WRONG_PASSWORD = 3;

  public static $messages = [
    self::MISSING_INFO   => '$LOGIN_MISSING_INFO',
    self::UNKNOWN_USER   => '$LOGIN_UNKNOWN_USER',
    self::WRONG_PASSWORD => '$LOGIN_WRONG_PASSWORD',
    self::DISABLED       => '$LOGIN_DISABLED',
  ];

  public function __construct ($code)
  {
    $this->code = $code;
    parent::__construct (self::$messages[$code], FlashType::WARNING);
  }

}
