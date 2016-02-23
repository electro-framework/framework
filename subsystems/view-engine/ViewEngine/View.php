<?php
namespace Selenia\ViewEngine;

use Selenia\Interfaces\Views\ViewEngineInterface;
use Selenia\Interfaces\Views\ViewInterface;

class View implements ViewInterface
{
  /**
   * @var mixed
   */
  private $compiled = null;
  /**
   * @var string
   */
  private $source = '';
  /**
   * @var ViewEngineInterface
   */
  private $viewEngine;

  public function __construct (ViewEngineInterface $viewEngine)
  {
    $this->viewEngine = $viewEngine;
  }

  /**
   * Compiles the template.
   *
   * @return $this
   */
  function compile ()
  {
    $this->compiled = $this->viewEngine->compile ($this->source);
    return $this;
  }

  /**
   * Gets the compiled template, if any.
   *
   * @return mixed|null
   */
  function getCompiled ()
  {
    return $this->compiled;
  }

  /**
   * Gets the associated rendering engine instance, if any.
   *
   * @return ViewEngineInterface|null
   */
  function getEngine ()
  {
    return $this->viewEngine;
  }

  /**
   * Gets the original source code (the template).
   *
   * @return string
   */
  function getSource ()
  {
    return $this->source;
  }

  /**
   * Sets the source code (the template).
   *
   * @param string $src
   * @return $this
   */
  function setSource ($src)
  {
    $this->source   = $src;
    $this->compiled = null;
    return $this;
  }

  /**
   * Renders the previously compiled template.
   *
   * @param array|object $data The view model; optional data for use by databinding expressions on the template.
   * @return string The generated output (ex: HTML).
   */
  function render ($data = null)
  {
    return $this->viewEngine->render ($this->compiled, $data);
  }
}
