<?php
namespace Selene\Routing;

interface IPage extends IRoute {

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
  function model ($v);
  /**
   *
   * @param string $v
   * @return $this
   */
  function view ($v);
  /**
   *
   * @param array $v
   * @return $this
   */
  function viewModel (array $v);
  /**
   *
   * @param string $v
   * @return $this
   */
  function controller ($v);
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
  /**
   * @param $v
   * @return $this
   */
  function isIndex ($v = true);
  /**
   * @param $v
   * @return $this
   */
  function links ($v);
  /**
   *
   * @param boolean $v
   * @return $this
   */
  function autoController ($v = true);
  /**
   *
   * @param array $v
   * @return $this
   */
  function preset ($v);

}