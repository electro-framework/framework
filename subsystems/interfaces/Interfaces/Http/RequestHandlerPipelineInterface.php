<?php
namespace Selenia\Interfaces\Http;

interface RequestHandlerPipelineInterface extends RequestHandlerInterface
{
  /**
   * Add a handler to the pipeline.
   *
   * @param string|callable|RequestHandlerInterface $handler The request handler to be added to the pipeline.
   * @param string|int|null                         $key     An ordinal index or an arbitrary identifier to associate
   *                                                         with the given handler.
   *                                                         <p>If not specified, an auto-incrementing integer index
   *                                                         will be assigned.
   *                                                         <p>If an integer is specified, it may cause the handler to
   *                                                         overwrite an existing handler at the same ordinal position
   *                                                         on the pipeline.
   *                                                         <p>String keys allow you to retrieve handlers by name.
   *                                                         <p>Some RequestHandlerPipelineInterface implementations
   *                                                         may
   *                                                         use the key for other purposes (ex. route matching
   *                                                         patterns).
   * @return $this
   */
  function add ($handler, $key = null);

  /**
   * Add a handler to the pipeline if a condition evaluates to `true`.
   *
   * @param boolean                                 $condition
   * @param string|callable|RequestHandlerInterface $handler The request handler to be added to the pipeline.
   * @param string|int|null                         $key     An ordinal index or an arbitrary identifier to associate
   *                                                         with the given handler.
   *                                                         <p>If not specified, an auto-incrementing integer index
   *                                                         will be assigned.
   *                                                         <p>If an integer is specified, it may cause the handler to
   *                                                         overwrite an existing handler at the same ordinal position
   *                                                         on the pipeline.
   *                                                         <p>String keys allow you to retrieve handlers by name.
   *                                                         <p>Some RequestHandlerPipelineInterface implementations
   *                                                         may
   *                                                         use the key for other purposes (ex. route matching
   *                                                         patterns).
   * @return $this
   */
  function addIf ($condition, $handler, $key = null);

  /**
   * Retrieves an handler from the pipeline.
   *
   * @param string|int $key An ordinal index or an arbitrary identifier associated with the desired handler.
   * @return string|callable|RequestHandlerInterface The request handler matching the given key.
   */
  function getHandlerAt ($key);

}
