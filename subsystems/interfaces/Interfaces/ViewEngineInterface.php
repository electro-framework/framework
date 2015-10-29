<?php
namespace Selenia\Interfaces;

interface ViewEngineInterface
{
  /**
   * Compiles the given template.
   * @param string $src The source markup (ex: HTML).
   * @return mixed The compiled template.
   */
  function compile ($src);

  /**
   * @param mixed $compiled The compiled template.
   * @param array $data The view model; optional data for use by databinding expressions on the template.
   * @return string The generated output (ex: HTML).
   */
  function render ($compiled, array $data = []);

}
