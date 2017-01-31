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
use Electro\ViewEngine\Config\ViewEngineSettings;
use Electro\ViewEngine\Lib\TemplateCache;
use PhpKit\Flow\FilesystemFlow;

class ViewService implements ViewServiceInterface
{
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
   * @var string[] A map of regular expression patterns to view engine class names.
   */
  private $patterns = [];

  function __construct (ViewEngineSettings $engineSettings, InjectorInterface $injector, TemplateCache $cache)
  {
    $this->injector       = $injector;
    $this->engineSettings = $engineSettings;
    $this->cache          = $cache;
  }

  function createViewModelFor (ViewInterface $view = null, $default = false)
  {
    if ($view && $path = $view->getPath ()) {

      foreach ($this->engineSettings->getViewModelNamespaces () as $nsPath => $ns) {
        if (str_beginsWith ($path, $nsPath)) {
          $remaining = substr ($path, strlen ($nsPath) + 1);
          $a         = PS ($remaining)->split ('/');
          $file      = $a->pop ();
          $nsPrefix  = $a->map ('ucfirst', false)->join ('\\')->S;
          $class     = ($p = strpos ($file, '.')) !== false
            ? ucfirst (substr ($file, 0, $p))
            : ucfirst ($file);
          $FQN       = PA ([$ns, $nsPrefix, $class])->prune ()->join ('\\')->S;
          if (class_exists ($FQN)) {
            $viewModel = $this->injector->make ($FQN);
            if ($viewModel instanceof ViewModel)
              return $viewModel;
            throw new \RuntimeException("Class <kbd>$FQN</kbd> does not implement " . ViewModel::class);
          }
        }
      }
    }
    return $default ? $this->injector->make (ViewModelInterface::class) : null;
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

  function register ($engineClass, $filePattern)
  {
    $this->patterns[$filePattern] = $engineClass;
    return $this;
  }

  public function resolveTemplatePath ($viewPath, &$base = null)
  {
    // Check if the path is a semi-absolute direct path to the file.
    if (file_exists ($viewPath)) {
      $base = '';
      return $viewPath;
    }
    // The path was not a direct path to the file; we must now search for the template on all registered directories.
    $dirs = $this->engineSettings->getDirectories ();
    foreach ($dirs as $base) {
      $p = "$base/$viewPath";
      if ($p = $this->findTemplate ($p))
        return $p;
    }
    // Throw an exception
    $paths = implode ('', map ($dirs, function ($path) {
      return "  <li><path>$path</path>
";
    }));
    throw new FileNotFoundException($viewPath, "
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
