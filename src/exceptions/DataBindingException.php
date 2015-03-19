<?php
namespace impactwave\matisse\exceptions;
use impactwave\matisse\Component;

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
