<?php
namespace Selene\Exceptions;

use Selene\Exceptions;

class FileWriteException extends Exceptions\BaseException
{

  public function __construct ($filename)
  {
    parent::__construct ("File <b>$filename</b> can't be written to.\nPlease check the permissions on the file or on the containing folder.",
      Exceptions\Status::FATAL);
  }

}