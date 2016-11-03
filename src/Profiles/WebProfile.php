<?php
namespace Electro\Profiles;

use Electro\Interfaces\ProfileInterface;

/**
 * This is an abstract configuration profile for web applications.
 *
 * <p>When testing `$profile instanceof WebProfile`, you can check if a module is being used on a web application or
 * not, irrespective of the concrete profile being used. Every web profile is therefore free to select any features it
 * needs without inheriting anything from this base class.
 */
abstract class WebProfile implements ProfileInterface
{
  public function getExcludedModules ()
  {
    return [];
  }

  public function getName ()
  {
    return str_segmentsLast (static::class, '\\');
  }

}
