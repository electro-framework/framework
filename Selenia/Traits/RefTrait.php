<?php
namespace Selenia\Traits;

use Selenia\Lib\Ref;

/**
 * Adds a `ref()` method to a class that can be used to generate callable references to methods on that class.
 * @see Ref
 */
trait RefTrait
{
  /** @var static */
  private $ref;

  /**
   * Returns a proxy that is able to generate callable references to methods on this class.
   * @return static
   */
  function ref ()
  {
    return $this->ref ?: $this->ref = new Ref ($this);
  }

}
