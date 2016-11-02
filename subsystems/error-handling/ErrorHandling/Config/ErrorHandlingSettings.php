<?php
namespace Electro\ErrorHandling\Config;

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
   */
  function getCustomRenderer ($status)
  {
    $renderer = get ($this->customRenderers, $status);
    return $renderer ? $this->injector->make ($renderer) : null;
  }

  /**
   * Defines a custom renderer for 'text/html' responses of a specific HTTP status code.
   * > <p>Codes unset will be handled by the default renderer.
   *
   * > <p>To render custom responses for other content types, you must override the {@see ErrorRendererInbterface}
   * service.
   *
   * @param int    $status The HTTP status code.
   * @param string $class  The renderer class name; the class must implement {@see RequestHandlerInterface},
   * @return $this For chaining.
   */
  function setCustomRenderer ($status, $class)
  {
    $this->customRenderers[$status] = $class;
    return $this;
  }

}
