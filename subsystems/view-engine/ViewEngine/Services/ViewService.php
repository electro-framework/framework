<?php

namespace Electro\ViewEngine\Services;

use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Exceptions\FatalException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\EventEmitterInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Traits\EventBroadcasterTrait;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Electro\ViewEngine\Lib\TemplateCache;
use Electro\ViewEngine\Lib\View;
use PhpKit\Flow\FilesystemFlow;

class ViewService implements ViewServiceInterface, EventEmitterInterface
{
  use EventBroadcasterTrait;

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

  function loadFromFile ($path, array $options = [])
  {
    $engine   = $this->getEngineFromFileName ($path, $options);
    $compiled = $engine->loadFromCache ($this->cache, $path);
    return $this->createFromCompiled ($compiled, $engine, $path);
  }

  function loadFromString ($src, $engineOrClass, array $options = [])
  {
    if (is_string ($engineOrClass))
      $engineOrClass = $this->getEngine ($engineOrClass, $options);
    // The injector is not used here. This service only returns instances of View.
    $view = new View ($engineOrClass, null, $this);
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

  public function resolveTemplatePath ($viewName, &$base = null)
  {
    $dirs = $this->engineSettings->getDirectories ();
    foreach ($dirs as $base) {
      $p = "$base/$viewName";
      if ($p = $this->findTemplate ($p))
        return $p;
    }
    // Throw an exception
    $paths = implode ('', map ($dirs, function ($path) {
      return "  <li><path>$path</path>
";
    }));
    throw new FileNotFoundException($viewName, "
<p>Search paths:

<ul>$paths</ul>");
  }

  /**
   * Creates a {@see View} instance from a compiled template.
   *
   * @param mixed                      $compiled
   * @param string|ViewEngineInterface $engineOrClass
   * @param string                     $path
   * @return View
   */
  private function createFromCompiled ($compiled, $engineOrClass, $path)
  {
    if (is_string ($engineOrClass))
      $engineOrClass = $this->getEngine ($engineOrClass);
    // The injector is not used here. This service only returns instances of View.
    $view = new View ($engineOrClass, $path, $this);
    $view->setCompiled ($compiled);
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
