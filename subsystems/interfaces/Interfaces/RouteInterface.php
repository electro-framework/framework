<?php
namespace Selenia\Interfaces;

/**
 * Represents the route that is being traversed as the current request's virtual URL is being routed.
 */
interface RouteInterface
{
  /**
   * The current location (URL segment).
   * > Ex: `'users'`
   *
   * <p>**Note:** Routes always begin with `''`, which corresponds to the start (root) location.
   *
   * @return string
   */
  function location ();

  /**
   * Proceed to the next location.
   *
   * @return RouteInterface|false A new RouteInterface instance who's current location has been moved to the next
   *                              location on that route. <code>false</code> if the current location is already the
   *                              last one.
   */
  function next ();

  /**
   * A map of URL parameters collected so far along the route.
   * > Ex: <code>['bookId' => 3, 'authorId' => 9]</code>
   *
   * @return string[]
   */
  function params ();

  /**
   * The full virtual URL up to the current location, inclusive.
   * > Ex: `'admin/users/3'`
   *
   * @return string
   */
  function path ();

  /**
   * The remaining path, starting after the current location.
   * > Ex: `'users/4'`
   *
   * @return string Empty string if the current location is the last on the route.
   */
  function tail ();

  /**
   * Is the current location the last location on the route?
   * @return bool
   */
  function target ();

}
