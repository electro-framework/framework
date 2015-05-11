<?php
namespace Selene;
use Selene\Exceptions\ConfigException;

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
        else throw new ConfigException ("Wrong number of arguments for setter <b>$p</b>.");
      }
      return $this;
    }
    else throw new ConfigException ("Invalid setter <b>$p</b> on an instance of <b>" . get_class () . '</b>.');
  }

}