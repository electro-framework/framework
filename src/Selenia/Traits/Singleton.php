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
    global $application;
    static $inst;
    if (!isset ($inst))
      $inst = new static ($application);
    return $inst;
  }

}
