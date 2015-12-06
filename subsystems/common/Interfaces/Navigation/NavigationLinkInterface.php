<?php
namespace Selenia\Interfaces\Navigation;

use Selenia\Exceptions\Fault;

interface NavigationLinkInterface extends \IteratorAggregate
{
  /**
   * The link's full URL or complete URL path.
   *
   * <p>It can be a path relative to the application's base path, an absolute path or a full URL address.
   *
   * <p>If the `url` property is not explicitly set, this is automatically computed from concatenating all subpaths
   * (static or dynamic) or URLs from all links on the trail that begins on the home/root link and ends on this link.
   *
   * <p>Example: **`'admin/users'`** (which is relative to the app's base path)
   *
   * <p>If any link on the trail defines an absolute path or an full URL, it will be used for computing the subsequent
   * links' URLs. If more than one absolute/full URL exist on the trail, the last one overrides previous ones.
   *
   * @return string|null If null, the link is disabled (it has no URL).
   */
  function actualUrl ();

  /**
   * Are links to this location enabled?
   *
   * <p>If disabled, the links will be shown but will not be actionable.
   *
   * <p>Default: **`true`**
   *
   * > ##### Dynamic evaluation
   * > Setting this property to a callback will make it dynamic and lazily evaluated.
   * > Reading the property (calling the method without an argument) will invoke the callback and return the resulting
   * > value.
   *
   * @param bool|callable $enabled
   * @return $this|bool $this if an argument is given, the property's value otherwise.
   */
  function enabled ($enabled = null);

  /**
   * The menu item's icon.
   *
   * @param string $icon A space-separated list of CSS class selectors. Ex: 'fa fa-home'
   * @return $this|string $this if an argument is given, the property's value otherwise.
   */
  function icon ($icon = null);

  /**
   * A unique name that identifies the link.
   *
   * <p>The ID allows you to reference the link elsewhere, for instance, when generating URLs for it.
   *
   * <p>Default: **`null`** (no ID)
   *
   * @param string $id
   * @return $this|string $this if an argument is given, the property's value otherwise.
   * @throws \InvalidArgumentException If any child has a duplicate ID on the current navigation tree.
   */
  function id ($id = null);

  /**
   * Indicates if the link is actually enabled, taking into account `enabled()`, and missing parameters on the URL.
   * @return bool
   * @throws Fault Faults::REQUEST_NOT_SET
   */
  function isActuallyEnabled ();

  /**
   * Indicates if the link is actually visible, taking into account `visible()`, `visibleIfUnavailable()` and missing
   * parameters on the URL.
   * @return bool
   * @throws Fault Faults::REQUEST_NOT_SET
   */
  function isActuallyVisible ();

  /**
   * Indicates if the link is a group pseudo-link that was created by a {@see NavigationInterface::group()} call.
   * @return bool
   */
  function isGroup ();

  /**
   * This link's navigation map (a map of child links).
   *
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this|NavigationLinkInterface[]|\Traversable|callable         $this if an argument is given, the
   *                                                                       property's value otherwise.
   *                                                                       <p>You should call <kbd>iterator($value)
   *                                                                       </kbd> on the returned instance to get an
   *                                                                       iterator that you can use to iterate the
   *                                                                       list of links.
   */
  function links ($navigationMap);

  /**
   * Merges a navigation map with this link's map.
   *
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this
   */
  function merge ($navigationMap);

  /**
   * The link's parent link or, if this is a root link, the navigation object.
   *
   * @param NavigationLinkInterface|NavigationInterface $parent
   * @return $this|NavigationLinkInterface|NavigationInterface $this if an argument is given, the property's value
   *                                                           otherwise.
   */
  function parent ($parent = null);

  /**
   * One or more segments of the full URL path that are contributed to it by this link. It is relative to the parent
   * link's subpath.
   *
   * <p>Example: the full path may be **`'admin/users/35/profile'`** and this link's subpath be **`'users/35'`**.
   *
   * > <p>**This is for internal use only.**
   *
   * > <p>Setting this property propagates `subpath` and `activeUrl` changes to all child links and their descendants.
   *
   * > <p>This will be called only once for each of the Navigation's root links to setup the whole application's
   * navigation.
   *
   * @param string $subpath If it's an integer, the subpath will be set to `null`, so it won't affect the computed URL.
   * @return $this|string|null $this if an argument is given, the property's value otherwise.
   */
  function subpath ($subpath = null);

  /**
   * The page title.
   *
   * <p>It may be displayed:
   * - on the browser's title bar and navigation history;
   * - on menus and navigation breadcrumbs.
   *
   * <p>Default: **`''`** (no title)
   *
   * > ##### Dynamic evaluation
   * > Setting this property to a callback will make it dynamic and lazily evaluated.
   * > Reading the property (calling the method without an argument) will invoke the callback and return the resulting
   * > value.
   *
   * @param string|callable $title
   * @return $this|string $this if an argument is given, the property's value otherwise.
   */
  function title ($title = null);

  /**
   * Set's an explicit URL for the link.
   *
   * <p>It can be a path relative to the application's base path, an absolute path or a full URL address.
   *
   * <p>Example: **`'admin/users'`** (which is relative to the app's base path)
   *
   * <p>You may explicitly override the `actualUrl` generated by default. The link's children URLs will be computed
   * relatively to this propery's value.
   *
   * > ##### Dynamic evaluation
   * > Setting this property to a callback will make it dynamic and lazily evaluated.
   * > Reading the property (calling the method without an argument) will invoke the callback and return the resulting
   * > value.
   *
   * @param string|callable $url
   * @return $this|string $this if an argument is given, the property's value otherwise.
   */
  function url ($url = null);

  /**
   * Are links to this location displayed?
   *
   * <p>If `false`, the links will not be shown on menus, but they'll still be shown in navigation breadcrumbs.
   *
   * <p>Default: **`true`**
   *
   * > ##### Dynamic evaluation
   * > Setting this property to a callback will make it dynamic and lazily evaluated.
   * > Reading the property (calling the method without an argument) will invoke the callback and return the resulting
   * > value.
   *
   * @param bool|callable $visible
   * @return $this|bool $this if an argument is given, the property's value otherwise.
   */
  function visible ($visible = null);

  /**
   * Are links to this location displayed even if the link's URL cannot be generated due to missing route parameters?
   *
   * <p>If `true`, the link will be shown on menus, but it'll be disabled (and greyed out) until the current route
   * provides all the parameters required for generating a valid URL for this link.
   *
   * <p>Enabling this setting can be used to show the user that there are more links available, even if the user cannot
   * select them until an additional selection is performed somehwere on those link's parent page.
   *
   * ###### Example
   *
   * For the following menu:
   *
   * - Authors
   *     - Books
   *     - Publications
   *
   * The children of the `Authors` menu item will only become enabled when the user selects an author on the `Authors`
   * page and the corresponding author ID becomes available on the URL.
   *
   * <p>Default: **`false`**
   *
   * @param bool $visible
   * @return $this|bool $this if an argument is given, the property's value otherwise.
   */
  function visibleIfUnavailable ($visible = null);

}
