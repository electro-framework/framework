<?php
namespace Selenia\Traits;

use Selenia\Exceptions\FatalException;

/**
 * Exposes selected properties from the object for being displayed by debugging tools.
 *
 * The static `$INSPECTABLE` property, if present on the class, specifies a list of properties (public, private or
 * protected) to be exposed.<br> If not present, all properties are exposed.
 *
 * > **Note:** you do not need to implement any interface to make use of this trait.
 * Just add `use InspectionTrait` to a class and it will work.
 */
trait InspectionTrait
{
  /**
   * **Note:** __debugInfo is a native PHP "magic" method.
   * @return array
   * @throws FatalException
   */
  function __debugInfo ()
  {
    if (isset (static::$INSPECTABLE)) {
      $o = [];
      $i = static::$INSPECTABLE;
      if (!is_array ($i))
        throw new FatalException (sprintf ('<kbd class=type>>%s::$INSPECTABLE</kbd> is not a valid list of property names.',
          get_class ()));
      foreach ($i as $prop) $o[$prop] = $this->$prop;
      return $o;
    }
    return get_object_vars ($this);
  }
}
