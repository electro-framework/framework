<?php
namespace Selenia\ViewEngine;

use Selenia\Application;
use Selenia\Exceptions\Fatal\FileNotFoundException;
use Selenia\Exceptions\FatalException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ViewEngineInterface;
use Selenia\Interfaces\ViewInterface;

class View implements ViewInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var mixed
   */
  private $compiled;
  /**
   * @var ViewEngineInterface
   */
  private $engine;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var string[] A map of regular expression patterns to view engine class names.
   */
  private $patterns = [];

  function __construct (Application $app, InjectorInterface $injector)
  {
    $this->injector = $injector;
    $this->app      = $app;
  }

  function getCompiledView ()
  {
    return $this->compiled;
  }

  function getEngine ()
  {
    return $this->engine;
  }

  function setEngine ($engineClass)
  {
    $this->engine = $this->injector->make ($engineClass);
    return $this;
  }

  function loadFromFile ($path)
  {
    $this->setEngineFromFileName ($path);
    $src = $this->loadView ($path);
    $this->loadFromString ($src);
    return $this;
  }

  function loadFromString ($src)
  {
    $this->compiled = $this->engine->compile ($src);
    return $this;
  }

  function register ($engineClass, $filePattern)
  {
    $this->patterns[$filePattern] = $engineClass;
    return $this;
  }

  function render ($data = null)
  {
    return $this->engine->render ($this->compiled, $data);
  }

  function setEngineFromFileName ($fileName)
  {
    foreach ($this->patterns as $pattern => $class)
      if (preg_match ($pattern, $fileName))
        return $this->engine = $this->injector->make ($class);
    throw new FatalException ("No registered view engine is capable of handling the file <b>$fileName</b>");
  }

  /**
   * Attempts to load the specified view file.
   * @param string $path
   * @return string The file's content.
   * @throws FileNotFoundException If the file was not found.
   */
  private function loadView ($path)
  {
    $dirs = $this->app->viewsDirectories;
    foreach ($dirs as $dir) {
      $p    = "$dir/$path";
      $view = loadFile ($p);
      if ($view)
        return $view;
    }
    $paths = implode ('', map ($dirs, function ($path) {
      return "<li><path>$path</path>";
    }));
    throw new FileNotFoundException($path, "<p>Search paths:<ul>$paths</ul>");
  }

}
