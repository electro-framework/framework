<?php
namespace Selene\Routing;

interface IRoute {

  /**
   *
   * @param string $v
   * @return $this
   */
  function URI ($v);
  /**
   * @param string $v Regular expression match.
   * @return $this
   */
  function match ($v);
  /**
   * # A map of HTTP verbs to handler callbacks.
   *
   *  Ex: `route()->on (['get' => function () { return 0; }])`
   * @param array $v
   * @return $this
   */
  function on ($v);
  /**
   * URI prefix to be added to all sub-routes.
   * @param string $v
   * @return $this
   */
  function prefix ($v);
  /**
   * Causes the specified module to be loaded.
   * @param string $v The module name (vendor/module).
   * @param array $config A map of one or more Settings that can be accessed by name.
   * @return $this
   */
  function module ($v, array $config = null);
  /**
   *
   * @param IRoute ...$v
   * @return $this
   */
  function routes (IRoute ...$v);
  /**
   * @param ...$v
   * @return $this
   */
  function middleware (...$v);
}