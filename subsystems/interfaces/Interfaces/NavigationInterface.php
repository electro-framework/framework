<?php
namespace Selenia\Interfaces;

interface NavigationInterface
{
  /**
   * Are links to this location enabled?
   *
   * <p>If disabled, the links will be shown but will not be actionable.
   *
   * @param bool $enabled
   * @return $this|bool
   */
  function enabled ($enabled = null);

  /**
   * @param string $icon
   * @return $this|string
   */
  function icon ($icon = null);

  /**
   * @param NavigationInterface[] $next
   * @return $this|NavigationInterface[]
   */
  function next (array $next);

  /**
   * The page title.
   *
   * <p>It may be displayed:
   * - on the browser's title bar and navigation history;
   * - on menus and navigation breadcrumbs.
   *
   * @param string $title
   * @return $this|string
   */
  function title ($title = null);

  /**
   * The relative URL path for the location this Navigation refers to.
   *
   * > Ex: `'admin/users'`
   *
   * @param string $url
   * @return $this|string
   */
  function url ($url = null);

  /**
   * Are links to this location displayed?
   *
   * <p>If `false`, the links will not be shown on menus, but they'll still be shown in navigation breadcrumbs.
   *
   * @param bool $visible
   * @return $this|bool
   */
  function visible ($visible = null);
}
