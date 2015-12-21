<?php
namespace Selenia\Matisse\Exceptions;
use Selenia\Matisse\Components\Base\Component;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false)
  {
    if (is_null ($component))
      parent::__construct ($msg);
    else {
      $i  = $this->inspect ($component, $deep);
      $id = $component->supportsAttributes && isset($component->props ()->id) ? $component->props ()->id : null;
      parent::__construct (
        $id
          ?
          "<blockquote>$msg</blockquote><code>$i</code>"
          :
          "<blockquote>$msg</blockquote><h4>$component->className instance's current attributes values:</h4><code>$i</code>"
        ,
        $id
          ?
          "Error on <b>$component->className</b> component <b>$id</b>"
          :
          "Error on a <b>$component->className</b> component"
      );
    }
  }

}
