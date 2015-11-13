<?php
namespace Selenia\Interfaces\Http;

/**
 * The route that is being traversed as the current request's virtual URL is being routed.
 *
 * ### Notes
 * - Instances implementing this interface **MUST** be immutable objects.
 * - New instances are created for each location as the request is being routed.
 * - `next()` creates new instances.
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
   * The virtual URL up to the current location, inclusive.
   * > Ex: on `'authors/4/books/7'`:
   * <br>if `location = '4'`, `path = 'authors/4'`.
   *
   * @return string
   */
  function path ();

  /**
   * The virtual URL up to the current location, but not including it.
   * > Ex: on `'authors/4/books/7'`:
   * <br>if `location = '4'`, `prefix = 'authors'`.
   *
   * @return string
   */
  function prefix ();

  /**
   * The remaining path, starting after the current location.
   * > Ex: on `'authors/4/books/7'`:
   * <br>if `location = '4'`, `tail = 'books/7'`.
   *
   * @return string Empty string if the current location is the last on the route.
   */
  function tail ();

  /**
   * The remaining path, starting on the current location.
   * > Ex: on `'authors/4/books/7'`:
   * <br>if `location = '4'`, `tail = '4/books/7'`.
   *
   * @return string Empty string if the current location is beyond the route or the route is empty.
   */
  function remaining ();

  /**
   * Is the current location the last location on the route?
   * @return bool
   */
  function target ();

}
