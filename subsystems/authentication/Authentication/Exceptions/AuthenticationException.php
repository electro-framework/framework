<?php
namespace Electro\Authentication\Exceptions;

use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;

class AuthenticationException extends FlashMessageException
{
  public function __construct ($message, $status = FlashType::WARNING, $title = '')
  {
    parent::__construct ($message, $status, $title);
  }
}
