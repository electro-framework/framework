<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Components;
use Selenia\Matisse\Parser\Context;

/**
 * View-related services.
 */
trait ViewsAPITrait
{
  /**
   * A mapping between modules view templates base directories and the corresponding PHP namespaces that will be
   * used for resolving view template paths to PHP controller classes.
   *
   * <p>**Note:** paths must have a trailing slash.
   *
   * @var array
   */
  public $controllerNamespaces = [];
  /**
   * A map of absolute view file paths to PHP controller class names.
   *
   * <p>This is used by the `Include` component.
   *
   * @var array
   */
  public $controllers = [];
  /**
   * The shared view-model data for the current rendering context.
   *
   * @var array
   */
  public $viewModel = [];
  /**
   * The view service that instantiated the current rendering engine and its associated rendering context (this
   * instance).
   *
   * @var ViewServiceInterface|null
   */
  public $viewService;

  /**
   * Attempts to find a controller class for the given view template path.
   *
   * @param string $viewName A view template absolute file path.
   * @return null|string null if no controller was found.
   */
  function findControllerForView ($viewName)
  {
    /** @var Context $this */
    $path = $this->viewService->resolveTemplatePath ($viewName);

    if (isset($this->controllers[$path]))
      return $this->controllers[$path];

    foreach ($this->controllerNamespaces as $base => $namespace)
      if (str_beginsWith ($path, $base)) {
        $segs = explode ('/', substr ($path, strlen ($base)));

        // Strip file extension(s) from filename
        $file = array_pop ($segs);
        if (($p = strpos ($file, '.')) !== false)
          $file = substr ($file, 0, $p);
        array_push ($segs, $file);

        $sub   = implode ('\\', map ($segs, 'ucwords'));
        $class = "$namespace\\$sub";
        if (class_exists ($class))
          return $class;
      }

    return null;
  }
}
