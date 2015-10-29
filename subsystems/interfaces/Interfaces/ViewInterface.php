<?php
namespace Selenia\Interfaces;

use Selenia\Exceptions\FatalException;

interface ViewInterface
{
  /**
   * Loads and compiles the specified template file.
   * @param string $path
   * @return $this
   */
  function loadFromFile ($path);

  /**
   * Compiles the given template.
   * > Don't forget to set a view engine before calling this method.
   * @param string $src
   * @return $this
   */
  function loadFromString ($src);

  /**
   * Registes a view engine to be used for rendering files that match the given regular expression pattern.
   * @param string $engineClass
   * @param string $filePattern A regular expression. Multiple patterns can be specified using the | operator.
   * @return $this
   */
  function register ($engineClass, $filePattern);

  /**
   * Renders the previously compiled template.
   * @param array $data The view model; optional data for use by databinding expressions on the template.
   * @return string The generated output (ex: HTML).
   */
  function render (array $data = []);

  /**
   * Instantiates the specified engine and sets it as the active engine for the view.
   * @param string $engineClass
   * @return $this
   */
  function setEngine ($engineClass);

  /**
   * @param string $fileName
   * @return ViewEngineInterface
   * @throws FatalException If no match was found.
   */
  function setEngineFromFileName ($fileName);

}
