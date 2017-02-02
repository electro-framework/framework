<?php

namespace Electro\ViewEngine\Services;

use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Exceptions\FatalException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Electro\Traits\EventsTrait;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Electro\ViewEngine\Lib\TemplateCache;
use PhpKit\Flow\FilesystemFlow;
use const Electro\Interfaces\Views\CREATE_VIEW_MODEL;
use const Electro\Interfaces\Views\RENDER;

class ViewService implements ViewServiceInterface
{
  use EventsTrait {
    emit as public;
  }

  /**
   * @var TemplateCache
   */
  private $cache;
  /**
   * @var ViewEngineSettings
   */
  private $engineSettings;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var string[] A map of regular expression patterns to view engine class names.
   */
  private $patterns = [];

  function __construct (ViewEngineSettings $engineSettings, InjectorInterface $injector, TemplateCache $cache,
                        KernelSettings $kernelSettings)
  {
    $this->injector       = $injector;
    $this->engineSettings = $engineSettings;
    $this->cache          = $cache;
    $this->kernelSettings = $kernelSettings;
  }

  function createViewModelFor (ViewInterface $view = null, $default = false)
  {
    if ($view && $path = $view->getPath ()) {
      $class = $this->getViewModelClass ($path);
      if ($class) {
        $viewModel = $this->injector->make ($class);
        if (!$viewModel instanceof ViewModel)
          throw new \RuntimeException("Class <kbd>$class</kbd> does not implement " . ViewModel::class);
      }
    }
    if (!isset ($viewModel)) {
      if (!$default) return null;
      $viewModel = $this->injector->make (ViewModelInterface::class);
    }
    $this->emit (CREATE_VIEW_MODEL, $view, $viewModel);
    return $viewModel;
  }

  function getEngine ($engineClass, $options = [])
  {
    // The engine class may receive this instance as a $view parameter on the constructor (optional).
    /** @var ViewEngineInterface $engine */
    $engine = $this->injector->make ($engineClass, [':view' => $this]);
    if ($options)
      $engine->configure ($options);
    return $engine;
  }

  function getEngineFromFileName ($path, $options = [])
  {
    foreach ($this->patterns as $pattern => $class)
      if (preg_match ($pattern, $path))
        return $this->getEngine ($class, $options);
    throw new FatalException ("None of the available view engines is capable of handling a file named <b>$path</b>.
<p>Make sure the file name has one of the supported file extensions or matches a known pattern.");
  }

  function getViewModelClass ($templatePath)
  {
    $this->resolveTemplatePath ($templatePath, $base, $viewPath);
    $class = ucwords (str_replace ('/', '\\', str_segmentsStripLast ($viewPath, '.')), '\\');

    foreach ($this->engineSettings->getViewModelNamespaces () as $ns) {
      $FQN = "$ns\\$class";
      if (class_exists ($FQN))
        return $FQN;
    }
    return null;
  }

  function loadFromFile ($viewPath, array $options = [])
  {
    if ($viewPath && $viewPath[0] == '/' || $viewPath[0] == '\\')
      throw new \RuntimeException("Invalid path <kbd>$viewPath</kbd>; it should be a relative view path");
    $semiAbsolutePath = $this->resolveTemplatePath ($viewPath);
    $engine           = $this->getEngineFromFileName ($semiAbsolutePath, $options);
    $compiled         = $engine->loadFromCache ($this->cache, $semiAbsolutePath);
    return $this->createFromCompiled ($compiled, $engine, $viewPath);
  }

  function loadFromString ($src, $engineOrClass, array $options = [])
  {
    if (is_string ($engineOrClass))
      $engineOrClass = $this->getEngine ($engineOrClass, $options);
    /** @var ViewInterface $view */
    $view = $this->injector->make (ViewInterface::class);
    $view->setEngine ($engineOrClass);
    $view->setSource ($src);
    $view->compile ();
    return $view;
  }

  public function loadViewTemplate ($path)
  {
    return loadFile ($path);
  }

  function onCreateViewModel (callable $handler)
  {
    return $this->on (CREATE_VIEW_MODEL, $handler);
  }

  function onRenderView (callable $handler)
  {
    return $this->on (RENDER, $handler);
  }

  function register ($engineClass, $filePattern)
  {
    $this->patterns[$filePattern] = $engineClass;
    return $this;
  }

  function __debugInfo ()
  {
    return [
      'View Engine Settings' => $this->engineSettings,
      'Registered Event Listeners' => $this->listeners
    ];
  }

  public function resolveTemplatePath ($path, &$base = null, &$viewPath = null)
  {
    if ($path[0] == '/' || $path[0] == '\\')
      throw new \InvalidArgumentException("Template path must be relative, either from a module's views directory or from the application's base directory");
    // Check if the path is a semi-absolute direct path to the file.
    if (str_beginsWith ($path, $this->kernelSettings->modulesPath) ||
        str_beginsWith ($path, $this->kernelSettings->pluginModulesPath)
    ) {
      if (func_num_args () > 1) {
        $dirs = $this->engineSettings->getDirectories ();
        foreach ($dirs as $base)
          if (str_beginsWith ($path, $base)) {
            $viewPath = substr ($path, strlen ($base) + 1);
            break;
          }
      }
      return $path;
    }
    // The path was not a direct path to the file; we must now search for the template on all registered directories.
    $dirs = $this->engineSettings->getDirectories ();
    foreach ($dirs as $base) {
      $p = "$base/$path";
      if ($p = $this->findTemplate ($p)) {
        $viewPath = $path;
        return $p;
      }
    }
    // Throw an exception
    $paths = implode ('', map ($dirs, function ($path) {
      return "  <li><path>$path</path>
";
    }));
    throw new FileNotFoundException($path, "
<p>Search paths:

<ul>$paths</ul>");
  }

  /**
   * Creates a {@see View} instance from a compiled template.
   *
   * @param mixed                      $compiled
   * @param string|ViewEngineInterface $engineOrClass
   * @param string                     $viewPath A relative view path.
   * @return ViewInterface
   * @throws \Auryn\InjectionException
   */
  private function createFromCompiled ($compiled, $engineOrClass, $viewPath)
  {
    if (is_string ($engineOrClass))
      $engineOrClass = $this->getEngine ($engineOrClass);
    /** @var ViewInterface $view */
    $view = $this->injector->make (ViewInterface::class);
    $view->setEngine ($engineOrClass);
    $view->setCompiled ($compiled);
    $view->setPath ($viewPath);
    return $view;
  }

  /**
   * Finds the file name extension for a given file path that has no extension.
   *
   * @param string $path The absolute path to a view template, with or without filename extension.
   * @return string|bool The complete file name or false if no suitable file was found.
   */
  private function findTemplate ($path)
  {
    if (file_exists ($path))
      return $path;
    return FilesystemFlow::glob ("$path.*")->onlyFiles ()->fetchKey ();
  }
}
