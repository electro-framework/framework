<?php
namespace Selenia\Exceptions;

use Exception;

class Fault extends \Exception
{
  public $type;

  public function __construct ($type, ...$args)
  {
    $this->type = $type;
    list (, $message) = explode ('|', $type, 2);
    parent::__construct (sprintf ($message, ...$args));
  }

}
