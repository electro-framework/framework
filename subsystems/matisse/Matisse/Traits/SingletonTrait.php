<?php
namespace Selenia\Matisse\Traits;

/**
 * Provides functionality for creating and accessing a single instance of a class.
 *
 * <p>To use this trait on a class, the class constructor **must** have no arguments.
 *
 * <p>When using this trait, you may also declare the class constructor as `private` to prevent further instantiation.
 * > <p>**Note:** this is a duplicate of `Selenia\Traits\SingletonTrait`, so that Matisse is completely independent
 * > from Selenia.
 */
trait SingletonTrait
{
  /** @var self */
  private static $it;

  static function instance ()
  {
    return self::$it ?: (self::$it = new static);
  }

  /**
   * Allows you to manually set or override the singleton instance.
   * <p>You may set an instance of any compatible class; for instance, a mock.
   * > This is useful mainly for automated testing.
   *
   * @param mixed $instance
   */
  static function setInstance ($instance)
  {
    self::$it = $instance;
  }

}
