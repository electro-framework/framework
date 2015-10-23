<?php
namespace Selenia\Traits;

/**
 * Provides the capability of setting class instance properties by using virtual fluent setter methods,
 * and reading those properties by `getXXX()` virtual methods.
 *
 * ### Setting array properties
 *
 * They should be declared with a default value of type array (even an empty one) to mark them as array properties.
 *   That way, a setter call with no arguments or with a single argument will set an array into the property.
 * ```
 *   class A {
 *   use FluentAPI;
 *     private $a = [];
 *     private $b;
 *   }
 *   (new A)->a ();     // a = []
 *   (new A)->a (1);    // a = [1]
 *   (new A)->a (1,2);  // a = [1,2]
 *   (new A)->a (null); // a = [null]
 *   (new A)->b ();     // b = true  just like a boolean prop.
 *   (new A)->b (1);    // b = 1
 *   (new A)->b (1,2);  // b = [1,2]
 * ```
 * ### Setting other property types
 *
 * A setter class with no arguments will set the property to `true`.
 * ```
 *   class A {
 *     use FluentAPI;
 *     private $enabled = false;
 *   }
 *   (new A)->enabled ();      // enabled = true
 *   (new A)->enabled (true);  // enabled = true
 *   (new A)->enabled (false); // enabled = false
 *   (new A)->enabled (null);  // enabled = false
 * ```
 *
 * ### Reading properties
 *
 * Prepend `get` to the property name and capitalize the first character of that name, then append `()`.
 *
 * Ex:
 * ```
 * $a = $b->getEnabled ();
 * ```
 */
trait FluentApi
{
  /**
   * It's triggered when invoking inaccessible methods in an object context.
   *
   * @param $name  string
   * @param $args  array
   * @return $this
   */
  function __call ($name, $args)
  {
    if (property_exists ($this, $name)) {
      if (is_array ($this->$name))
        $this->$name = $args;
      else if (is_bool ($this->$name))
        $this->$name = $args;
      else {
        switch (count ($args)) {
          case 0:
            $v = true;
            break;
          case 1:
            $v = $args[0];
            break;
          default:
            $v = $args;
        }
        $this->$name = $v;
      }
      return $this;
    }
    throw new \BadMethodCallException;
  }

}
