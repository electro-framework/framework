<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class SessionException extends Exceptions\BaseException
{
  const MISSING_INFO   = 1;
  const UNKNOWN_USER   = 2;
  const WRONG_PASSWORD = 3;
  const DISABLED       = 4;

  public static $messages = [
    self::MISSING_INFO   => '$LOGIN_MISSING_INFO',
    self::UNKNOWN_USER   => '$LOGIN_UNKNOWN_USER',
    self::WRONG_PASSWORD => '$LOGIN_WRONG_PASSWORD',
    self::DISABLED       => '$LOGIN_DISABLED'
  ];

  public function __construct ($code)
  {
    $this->code = $code;
    parent::__construct (self::$messages[$code], Exceptions\Status::WARNING);
  }

}
