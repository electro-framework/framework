<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class FatalException extends Exceptions\BaseException
{

  public function __construct ($msg, $title = '')
  {
    parent::__construct ($msg, Exceptions\Status::FATAL, $title);
  }

}
