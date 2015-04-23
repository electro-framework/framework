<?php
namespace selene\matisse\exceptions;
use selene\matisse\Component;

class DataBindingException extends MatisseException
{
  public function __construct (Component $component = null, $msg)
  {
    if (isset($component))
      $i = $this->inspect ($component);
    else
      $i = '';
    parent::__construct ("$msg\n\n$i", 'Databinding error');
  }

}
