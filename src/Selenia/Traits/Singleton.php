<?php
namespace Selenia\Traits;

trait Singleton
{
  /**
   * Gets the singleton instance of this class.
   * @return $this
   */
  static function get ()
  {
    static $inst;
    return isset ($inst) ? $inst : $inst = new static;
  }

}
