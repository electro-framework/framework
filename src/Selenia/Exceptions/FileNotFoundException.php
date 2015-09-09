<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class FileNotFoundException extends Exceptions\BaseException
{

  public function __construct ($filename)
  {
    parent::__construct ("File <b>$filename</b> was not found.", Exceptions\Status::FATAL);
  }

}
