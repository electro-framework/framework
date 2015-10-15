<?php
namespace Selenia;

/**
 * Represents a Selenia module.
 *
 * Statically, this class provides an API for querying module information.
 *
 * Instances of subclasses of this class represent specific modules and provide initialization and configuration methods
 * for them.
 */
class Module
{
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }


}
