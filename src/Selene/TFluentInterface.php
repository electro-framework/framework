<?php
namespace Selene;

/**
 * Note: do not initalize your subclass properties with an empty array if the property will hold an associative array.
 */
trait TFluentInterface
{

  function __call ($p, $a)
  {
    if (property_exists ($this, $p)) {
      if (is_array ($this->$p))
        $this->$p = $a;
      else {
        $c = count ($a);
        if ($c < 2)
          $this->$p = !$c ?: $a[0];
        else throw new \RuntimeException ("Wrong number of arguments for setter $p.");
      }
      return $this;
    }
    else throw new \RuntimeException ("Invalid setter $p.");
  }

}