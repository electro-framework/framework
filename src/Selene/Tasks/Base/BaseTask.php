<?php
namespace Selene\Tasks\Base;
use Robo\Contract\TaskInterface;

abstract class BaseTask implements TaskInterface
{
  /**
   * It's triggered when invoking inaccessible methods in an object context.
   *
   * @param $name  string
   * @param $args  array
   * @return $this
   * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods
   */
  function __call ($name, $args)
  {
    if (property_exists ($this, $name)) {
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

      return $this;
    }
    throw new \BadMethodCallException;
  }

  /**
   * Runs the task.
   * @return \Robo\Result
   */
  abstract function run ();
}
