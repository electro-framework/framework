<?php
namespace Selenia\Interfaces\Views;

/**
 * A low-level API to a single view/templating engine, which is capable of compiling and rendering templates coded in a
 * specific templating language.
 */
interface ViewEngineInterface
{
  /**
   * Compiles the given template.
   * @param string $src The source markup (ex: HTML).
   * @return mixed The compiled template.
   */
  function compile ($src);

  /**
   * @param mixed        $compiled The compiled template.
   * @param array|object $data     The view model; optional data for use by databinding expressions on the template.
   * @return string The generated output (ex: HTML).
   */
  function render ($compiled, $data = null);

}
