<?php
namespace Selenia\Exceptions;

use Exception;

class FlashMessageException extends Exception
{
  protected $status;
  public    $title;

  public function __construct ($message, $status, $title = '')
  {
    $this->title  = $title;
    parent::__construct ($message, $status);
  }

}
