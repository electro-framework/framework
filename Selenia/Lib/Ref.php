<?php
namespace Selenia\Lib;

/**
 * Generates refactor-friendly class method references.
 * <p>It proxies a class instance so that calling a method on the proxy returns a callable reference to that method on
 * the original instance.
 * <p>This is more useful if used in conjunction with the `RefTrait`.
 * > Ex:
 * ```
 *   return $instance->ref()->method();
 * ```
 * > instead of:
 * ```
 *   return [$instance, 'method'];
 * ```
 * > The former case, though a little more verbose, has a major advantage: it is refactor-friendly.
 */
class Ref
{
  /** @var object */
  private $target;

  /**
   * Ref constructor.
   * @param object $instance
   */
  public function __construct ($instance)
  {
    $this->target = $instance;
  }

  function __call ($name, $args)
  {
    if ($args)
      throw new \BadMethodCallException("ref()->xxx() calls should have no arguments.");
    return [$this->target, $name];
  }

}
