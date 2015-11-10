<?php
namespace Selenia\Interfaces;

interface NavigationInterface
{
  /**
   * @param string $title
   * @return $this|string
   */
  function title ($title);

  /**
   * @param bool $enabled
   * @return $this|bool
   */
  function enabled ($enabled);

  /**
   * @param bool $visible
   * @return $this|bool
   */
  function visible ($visible);

  /**
   * @param string $icon
   * @return $this|string
   */
  function icon ($icon);

  /**
   * @param string $url
   * @return $this|string
   */
  function url ($url);

  /**
   * @param NavigationInterface[] $next
   * @return $this|NavigationInterface[]
   */
  function next (array $next);
}
