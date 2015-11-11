<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface RouteInterface
{
  /**
   * The current location (URL segment).
   * > Ex: `'users'`
   * @return string
   */
  function location ();

  /**
   * Tries to match the current location to one of a set of constant locations and, if a match is found, invokes the
   * corresponding handler.
   * <p>Handlers must have a RouterInterface callable signature and should return a new or modified HTTP response. If a
   * handler decides to not handle a request, it may invoke the `$next` argument and route matching will proceed..
   *
   * @param array $locations A map of literal location strings to handler callables.
   * @return $this
   */
  function map (array $locations);

  /**
   * @param callable $run
   * @return $this
   */
  function matches (callable $run);

  /**
   * @param string $name
   * @param string $handler
   * @return $this
   */
  function param ($name, $handler);

  /**
   * The full virtual URL.
   * > Ex: `'/admin/users/3'`
   * @return string
   */
  function path ();

  /**
   *
   * @param ResponseInterface $response
   * @return ResponseInterface
   */
  function next (ResponseInterface $response = null);
}
