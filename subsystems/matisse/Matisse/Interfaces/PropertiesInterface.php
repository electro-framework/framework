<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Properties\Base\ComponentProperties;

/**
 * Components that implement this interface support attributes, whose values are usually specified on the HTML markup
 * of web pages.
 */
interface PropertiesInterface
{
  /**
   * Returns the component's attributes.
   * This method must be redefined in each subclass so that it will provide the correct keyword completion on the IDE.
   * @return ComponentProperties
   */
  function props ();

  /**
   * Creates an instance of the component's attributes.
   * This method must be redefined in each subclass so that it will provide the correct keyword completion on the IDE.
   * @return ComponentProperties
   */
  function newProperties ();
}
