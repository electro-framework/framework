<?php
namespace selene\matisse;

/**
 * Components that implement this interface support attributes, whose values are usually specified on the HTML markup
 * of web pages.
 */
interface IAttributes
{
  /**
   * Returns the component's attributes.
   * This method must be redefined in each subclass so that it will provide the correct keyword completion on the IDE.
   * @return ComponentAttributes
   */
  function attrs ();

  /**
   * Creates an instance of the component's attributes.
   * This method must be redefined in each subclass so that it will provide the correct keyword completion on the IDE.
   * @return ComponentAttributes
   */
  function newAttributes ();
}
