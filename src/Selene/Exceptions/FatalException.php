<?php
namespace Selene\Exceptions;

use Selene\Exceptions;

class FatalException extends Exceptions\BaseException
{

  public function __construct ($msg, $title = '')
  {
    parent::__construct ($msg, Exceptions\Status::FATAL, $title);
  }

}