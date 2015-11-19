<?php
namespace Selenia\Interfaces\Http;

/**
 * A pipeline of request handlers.
 * <p>By invoking an instance that implements this interface, the pipeline is executed and a response may be generated
 * for a given request.
 * <p>There are no methods to retrieve the content of the pipeline; this is by design.
 * > **Note:** instances are not immutable, but calling `with()` may return a new instance.
 */
interface RequestHandlerPipelineInterface extends RequestHandlerInterface
{
  /**
   * Adds a request handler to the pipeline.
   * @param string|callable|RequestHandlerInterface $handler The request handler to be added to the pipeline.
   * @param string|int|null                         $key     An ordinal index or an arbitrary identifier to associate
   *                                                         with the given handler.
   *                                                         <p>If not specified, an auto-incrementing integer index
   *                                                         will be assigned.
   *                                                         <p>If an integer is specified, it may cause the handler to
   *                                                         overwrite an existing handler at the same ordinal position
   *                                                         on the pipeline.
   *                                                         <p>String keys allow you to insert new handlers after a
   *                                                         specific one.
   *                                                         <p>Some RequestHandlerPipelineInterface implementations
   *                                                         may use the key for other purposes (ex. route matching
   *                                                         patterns).
   * @param string|int|null                         $after   Insert after an existing handler that lies at the given
   *                                                         index, or that has the given key. When null, it is
   *                                                         appended.
   * @return $this
   */
  function add ($handler, $key = null, $after = null);

  /**
   * Sets the pipeline to the given one.
   *
   * @param mixed $handlers An array, Traversable, callable or class name. If the argument is a handler, it's equivalent
   *                        to creating a pipeline with a single handler (but it may not be implemented as such).
   * @return $this
   */
  function set ($handlers);

  /**
   * Creates a new instance of this class with the given pipeline.
   * > **Note:** a call to this method when the current pipeline is empty will return the same instance
   * (micro-optimization).
   *
   * @param mixed $handlers An array, Traversable, callable or class name. If the argument is a handler, it's equivalent
   *                        to creating a pipeline with a single handler (but it may not be implemented as such).
   * @return $this
   */
  function with ($handlers);

}
