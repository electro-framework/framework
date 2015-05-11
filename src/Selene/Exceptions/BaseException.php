<?php
namespace Selene\Exceptions;

use Exception;
use Selene\Matisse\Component;

class BaseException extends Exception
{

  protected $status;
  public    $title;

  public function __construct ($message, $status, $title = '')
  {
    $this->status = $status;
    $this->title  = $title;
    parent::__construct ($message, 0);
  }

  public function getStatus ()
  {
    return $this->status;
  }

  protected function inspect (Component $component, $deep = false)
  {
    ob_start ();
    $component->inspect ($deep);
    return ob_get_clean ();
  }

}