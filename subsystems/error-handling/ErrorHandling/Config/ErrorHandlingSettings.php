<?php
namespace Electro\ErrorHandling\Config;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\DI\InjectableFunction;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;

/**
 * Configuration settings for the Error Handling subsystem.
 */
class ErrorHandlingSettings
{
  /**
   * Map of `int => string`, mapping status codes to class names. Codes unset will be handled by the default renderer.
   *
   * @var string[]
   */
  private $customRenderers = [];
  /**
   * @var InjectorInterface
   */
  private $injector;

  public function __construct (
    InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * Retrieves a custom renderer instance for 'text/html' responses of a specific HTTP status code.
   *
   * @param int $status The HTTP status code.
   * @return RequestHandlerInterface|null
   * @throws ConfigException
   * @throws \Auryn\InjectionException
   */
  function getCustomRenderer ($status)
  {
    $renderer = get ($this->customRenderers, $status);
    if ($renderer) {
      if ($renderer instanceof InjectableFunction)
        return $this->injector->execute ($renderer ());
      if (is_callable ($renderer))
        return $renderer;
      if (is_string ($renderer))
        return $this->injector->make ($renderer);
      throw new ConfigException("Invalid value for defining a custom error renderer");
    }
    return null;
  }

  /**
   * Defines a custom renderer for 'text/html' responses of a specific HTTP status code.
   * > <p>Codes not set will be handled by the default renderer.
   *
   * > <p>To render custom responses for other content types, you must override the {@see ErrorRendererInterface}
   * service.
   *
   * @param int    $status The HTTP status code.
   * @param string $class A routable instance. It can be one of the following types:<ol>
   *                      <li>The name of an injectable class that implements {@see RequestHandlerInterface}
   *                      <li>A callable request handler (ex: a function compatible with {@see RequestHandlerInterface}
   *                      <li>A {@see FactoryRoutable} instance.
   *                      </ol>
   * @return $this Self for chaining.
   */
  function setCustomRenderer ($status, $class)
  {
    $this->customRenderers[$status] = $class;
    return $this;
  }

}
