<?php
namespace Selenia\Matisse\Exceptions;
use Selenia\Matisse\Components\Base\Component;

class DataBindingException extends MatisseException
{
  public function __construct (Component $component = null, $msg, \Exception $previous = null)
  {
    if (isset($component))
      $i = $this->inspect ($component);
    else
      $i = '';
    parent::__construct ("<p>$msg</p>$i", 'Databinding error', $previous);
  }

}
