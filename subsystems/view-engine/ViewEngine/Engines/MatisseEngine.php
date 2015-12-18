<?php
namespace Selenia\ViewEngine\Engines;

use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Views\ViewEngineInterface;
use Selenia\Matisse\Components\Internal\Page;
use Selenia\Matisse\MatisseEngine as Matisse;
use Selenia\Matisse\PipeHandler;

class MatisseEngine implements ViewEngineInterface
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
   * @var Matisse
   */
  private $matisse;
  /**
   * @var PipeHandler
   */
  private $pipeHandler;

  function __construct (Matisse $matisse, PipeHandler $pipeHandler, Application $app, InjectorInterface $injector)
  {
    $this->matisse     = $matisse;
    $this->pipeHandler = $pipeHandler;
    $this->app         = $app;
    $this->injector    = $injector;
  }

  function compile ($src)
  {
    // Setup Matisse pipes.

    $pipeHandler = clone $this->pipeHandler;
//    $pipeHandler->registerFallbackHandler ($controller);

    // Setup a rendering context.

    $ctx = $this->matisse->createContext ($this->app->tags, $pipeHandler);

    $ctx->condenseLiterals  = $this->app->condenseLiterals;
    $ctx->debugMode         = $this->app->debugMode;
    $ctx->macrosDirectories = $this->app->macrosDirectories;
    $ctx->presets           = map ($this->app->presets,
      function ($class) { return $this->app->injector->make ($class); });
    $ctx->macrosExt         = '.html';
    $ctx->injectorFn        = function ($name) {
      return $this->injector->make ($name);
    };

    // Create a compiled template.

    $page = new Page($ctx);
    return $this->matisse->parse ($src, $ctx, $page);
  }

  function render ($compiled, $data = null)
  {
    /** @var Page $compiled */
    $compiled->context->viewModel = $data;
    return $this->matisse->render ($compiled);
  }
}
