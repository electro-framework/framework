<?php
namespace Selene\Matisse\Exceptions;
use Selene\Matisse\Component;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false)
  {
    if (is_null ($component))
      parent::__construct ($msg);
    else {
      $i  = $this->inspect ($component, $deep);
      $id = $component->supportsAttributes && isset($component->attrs ()->id) ? $component->attrs ()->id : null;
      parent::__construct (
        $id
          ?
          "<blockquote>$msg</blockquote>$i"
          :
          "<blockquote>$msg</blockquote>Component attributes (no child components are shown):\n$i"
        ,
        $id
          ?
          "Error on <b>$component->className</b> component <b>$id</b>"
          :
          "Error on a <b>$component->className</b> component",
        'Component error');
    }
  }

}
