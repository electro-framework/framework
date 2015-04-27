<?php
namespace Selene\Matisse\Exceptions;
use Selene\Matisse\Component;

class MatisseException extends \Exception
{
  public $title;

  public function __construct ($message, $title = '')
  {
    $this->title = $title;
    parent::__construct ($message, 0);
  }

  protected function inspect (Component $component, $deep = false)
  {
    ob_start ();
    $component->inspect ($deep);
    return ob_get_clean ();
  }

}
