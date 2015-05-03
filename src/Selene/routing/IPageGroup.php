<?php
namespace Selene\Routing;

interface IPageGroup extends IRoute {

  /**
   *
   * @param string $v
   * @return $this
   */
  function title ($v);
  /**
   *
   * @param string $v
   * @return $this
   */
  function defaultURI ($v);
  /**
   *
   * @param boolean $v
   * @return $this
   */
  function onMenu ($v = true);
  /**
   *
   * @param string $v
   * @return $this
   */
  function icon ($v);
}