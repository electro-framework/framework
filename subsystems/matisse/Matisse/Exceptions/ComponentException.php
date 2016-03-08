<?php
namespace Selenia\Matisse\Exceptions;

use PhpKit\WebConsole\Lib\Debug;
use Selenia\Matisse\Components\Base\Component;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false)
  {
    if (is_null ($component))
      parent::__construct ($msg);
    else {
      $i     = $this->inspect ($component, $deep);
      $props = isset($component->props) ? $component->props->getBeingAssigned () : [];
      $o     = $props ? Debug::properties($props) : '';
      $id    = $component->supportsProperties && isset($component->props->id) ? $component->props->id : null;
      $class = typeInfoOf ($component);
      // Append a period, if applicable.
      if (ctype_alnum (substr ($msg, -1)))
        $msg .= '.';
      parent::__construct (
        !$component->props || !$component->props->getAll ()
          ? "On a $class instance.<br><br><blockquote>$msg</blockquote>$o"
          : "<blockquote>$msg</blockquote>$o<p>$class instance's current attributes values:</p>$i"
        ,
        $id
          ?
          "Error on $class component <b>$id</b>"
          :
          "Error on a $class component"
      );
    }
  }

}
