<?php
namespace Electro\DependencyInjection;

/**
 * Enables an injectable service to mediate access to a mutable target service instance.
 *
 * <p>Steps to use this:
 * - Create a class that implements the trait and name it XxxMutator (where Xxx is the target service name)
 * - Share the class on the injector.
 * - Inject an instance of it wherever you need access to the underlying service.
 * - Always call the mutator's `set()` at least once before accessing the target service.
 */
trait MutatorTrait
{
  private $value;

  function set ($value) {
    $this->value = $value;
    return $this;
  }

  function get () {
    return $this->value;
  }
}
