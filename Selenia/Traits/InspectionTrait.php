<?php
namespace Selenia\Traits;

use Selenia\Exceptions\FatalException;

trait InspectionTrait
{
  function __debugInfo ()
  {
    $o = [];
    if (!isset (static::$INSPECTABLE))
      throw new FatalException ('The <kbd>' . get_class () . '::$INSPECTABLE</kbd> static property is expected but it\'s not defined.');
    $i = static::$INSPECTABLE;
    if (!is_array ($i))
      throw new FatalException ('<kbd>' . get_class () . '::$INSPECTABLE</kbd> is not a valid list of property names.');
    foreach ($i as $prop) $o[$prop] = $this->$prop;

    return $o;
  }
}
