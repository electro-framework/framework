<?php
namespace Selene\Matisse;

use Selene\Matisse\Exceptions\HandlerNotFoundException;

/**
 * Resolves pipe invocations to pipe implementations,
 *
 * The registered pipes will be available for use on databinding expressions when rendering.
 */
class PipeHandler
{
  /**
   * Map of pipe names to pipe implementation functions.
   *
   * Pipes can be used on databinding expressions. Ex: {!a.c|myPipe}
   * @var array
   */
  private $pipes = [];

  /**
   * If not match for a pipe is found on the map of registered pipes, a call will be made on the fallback handler
   * object to a method named `$pipeName . '_pipe'`.
   *
   * > The handler must provide concrete methods for the pipes; dynamic (magic) invocations are not supported.
   *
   * @var object
   */
  private $fallbackHandler;

  /**
   * Register a set of pipes for use on databinding expressions when rendering.
   * @param array|object $pipes Either a map of pipe names to pipe implementation functions or an instance of a class
   *                            where each public method (except the constructor) is a named pipe function.
   */
  function registerPipes ($pipes)
  {
    if (is_object ($pipes)) {
      $keys   = array_diff (get_class_methods ($pipes), ['__construct']);
      $values = array_map (function ($v) use ($pipes) { return [$pipes, $v]; }, $keys);
      $pipes  = array_combine ($keys, $values);
    };
    $this->pipes = array_merge ($this->pipes, $pipes);
  }

  function registerFallbackHandler ($handler)
  {
    $this->fallbackHandler = $handler;
  }

  function __call ($name, $args)
  {
    if (isset($this->pipes[$name]))
      return call_user_func_array ($this->pipes[$name], $args);

    if (isset($this->fallbackHandler)) {
      $method = $name . '_pipe';
      if (method_exists ($this->fallbackHandler, $method))
        return call_user_func_array ([$this->fallbackHandler, $method], $args);
    }
    throw new HandlerNotFoundException;
  }

}