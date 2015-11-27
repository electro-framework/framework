<?php
namespace Selenia\Interfaces\Navigation;

use SplObjectStorage;

/**
 * Represents a set of navigation link trees and provides an API for performing operations on it.
 *
 * <p>It also provides functionality for generating links, menus and breadcrumb navigations.
 *
 * <p>There is, usually, one single instance of this interface for the whole application, unless you need to generate
 * additional menus.
 * <p>Modules may provide their own navigation maps that will be merged into the shared navigation instance.<br>
 * That shared instance will be responsable for generating the application's main menu, links and breadcrumbs.
 */
interface NavigationInterface extends \IteratorAggregate
{
  /**
   * Merges a navigation map onto this navigation.
   *
   * ><p>The actual merging is postponed until the navigation needs to be read.
   *
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this
   * @throws \InvalidArgumentException If the argument is not iterable.
   */
  function add ($navigationMap);

  /**
   * Builds/updates the path of {ßee NavigationLinkInterface} instances for the given relative URL path.
   * @param string $url
   * @return $this
   */
  function buildPath ($url);

  /**
   * A linear sequence of {@see NavigationLinkInterface} objects that represents the path to the currently displayed
   * page starting from a root (home) link.
   *
   * <p>The set can be enumerated as a list of objects, with sequential integer keys.
   * > No `SplObjectStorage` extra data is associated with elements on the set.
   *
   * <p>All objects on the path are also on the navigation tree.
   *
   * <p>This also allows one to test if a link on the tree is also on the path (for instance, for deciding if a link
   * on a menu is selected). Use {@see SplObjectStorage::contains()} to check for that.
   *
   * @param SplObjectStorage|null $path
   * @return $this|SplObjectStorage
   */
  function currentTrail (SplObjectStorage $path = null);

  /**
   * Returns a set of registered IDs and their corresponding links.
   *
   * <p>IDs are registered automatically when a call to setId() is done on a link generated from this instance using
   * {@see link()}.
   *
   * @return NavigationLinkInterface[] A map of ID => NavigationLinkInterface
   */
  function getIds ();

  /**
   * Returns a linear list of all links that are relevant to build a menu.
   * @return NavigationLinkInterface[]
   */
  function getTree ();

  /**
   * Creates a new navigation group object, bound to this Navigation.
   *
   * <p>A group is a Link that, when displayed as a hyperlink, has a no-action URL, so that the browser does not
   * navigate when the user clicks it.
   *
   * <p>It is useful to group child links on a menu or to display a organizational pseudo-link on a breadcrumb.
   *
   * @return NavigationLinkInterface
   */
  function group ();

  /**
   * Creates a new navigation link object, bound to this Navigation.
   * @return NavigationLinkInterface
   */
  function link ();

}
