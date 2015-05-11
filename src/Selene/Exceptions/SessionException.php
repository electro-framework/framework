<?php
namespace Selene\Exceptions;

use Selene\Exceptions;

class SessionException extends Exceptions\BaseException
{
  const MISSING_INFO   = 1;
  const UNKNOWN_USER   = 2;
  const WRONG_PASSWORD = 3;
  const NO_SESSION     = 4;

  public static $messages = [
    self::MISSING_INFO   => "Por favor especifique um utilizador e a respectiva senha.",
    self::UNKNOWN_USER   => "Utilizador desconhecido.",
    self::WRONG_PASSWORD => "A senha está incorrecta.",
    self::NO_SESSION     => 'Não há qualquer sessão activa.'
  ];

  public function __construct ($code)
  {
    $this->code = $code;
    parent::__construct (self::$messages[$code], Exceptions\Status::WARNING);
  }

}