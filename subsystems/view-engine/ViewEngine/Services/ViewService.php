<?php
namespace Electro\ViewEngine\Services;

use Electro\Application;
use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Exceptions\FatalException;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\ViewEngine\Lib\View;
use PhpKit\Flow\FilesystemFlow;

class ViewService implements ViewServiceInterface
{
  /**
   * @var Application
   */
  private $app;
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

  function getEngine ($engineClass)
  {
    // The engine class may receive this insstance as a $view parameter on the constructor (optional).
    $engine = $this->injector->make ($engineClass, [':view' => $this]);
    return $engine;
  }

  function getEngineFromFileName ($fileName)
  {
    foreach ($this->patterns as $pattern => $class)
      if (preg_match ($pattern, $fileName))
        return $this->getEngine ($class);
    throw new FatalException ("None of the available view engines is capable of handling a file named <b>$fileName</b>.
<p>Make sure the file name has one of the supported file extensions or matches a known pattern.");
  }

  function loadFromFile ($path)
  {
    $engine = $this->getEngineFromFileName ($path);
    $src    = $this->loadViewTemplate ($path);
    return $this->loadFromString ($src, $engine);
  }

  function loadFromString ($src, $engineOrClass)
  {
    if (is_string ($engineOrClass))
      $engineOrClass = $this->getEngine ($engineOrClass);
    // The injector is not used here. This service only returns instances of View.
    $view = new View($engineOrClass);
    $view->setSource ($src);
    return $view;
  }

  public function loadViewTemplate ($path)
  {
    $realPath = $this->resolveTemplatePath ($path);
    $file = loadFile ($realPath);
    if ($file) return $file;
    $iter = FilesystemFlow::glob("$path.*")->onlyFiles()->getIterator();
    return $iter->valid() ? $iter->key() : false;
  }

  function register ($engineClass, $filePattern)
  {
    $this->patterns[$filePattern] = $engineClass;
    return $this;
  }

  public function resolveTemplatePath ($path, &$base = null)
  {
    $dirs = $this->app->viewsDirectories;
    foreach ($dirs as $base) {
      $p = "$base/$path";
      if (fileExists ($p))
        return $p;
    }
    $paths = implode ('', map ($dirs, function ($path) {
      return "<li><path>$path</path>";
    }));
    throw new FileNotFoundException($path, "<p>Search paths:<ul>$paths</ul>");
  }

}
