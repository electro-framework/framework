<?php
namespace selene\matisse\exceptions;
use selene\matisse\Component;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false)
  {
    if (is_null ($component))
      parent::__construct ($msg);
    else {
      $i = $this->inspect ($component, $deep);
      parent::__construct (
        $component->supportsAttributes && isset($component->attrs ()->id)
          ?
          "Error on <b>$component->className</b> component <b>{$component->attrs ()->id}</b>:<blockquote>$msg</blockquote>$i"
          :
          "Error on a <b>$component->className</b> component:<blockquote>$msg</blockquote>Component attributes (no child components are shown):\n$i"
        , 'Component error');
    }
  }

}
