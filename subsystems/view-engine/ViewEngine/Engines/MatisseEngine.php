<?php
namespace Selenia\ViewEngine\Engines;

use Selenia\Application;
use Selenia\Interfaces\ViewEngineInterface;
use Selenia\Matisse\Components\Page;
use Selenia\Matisse\MatisseEngine as Matisse;
use Selenia\Matisse\PipeHandler;

class MatisseEngine implements ViewEngineInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var Matisse
   */
  private $matisse;
  /**
   * @var PipeHandler
   */
  private $pipeHandler;

  function __construct (Matisse $matisse, PipeHandler $pipeHandler, Application $app)
  {
    $this->matisse     = $matisse;
    $this->pipeHandler = $pipeHandler;
    $this->app         = $app;
  }

  function compile ($src)
  {
    // Setup Matisse pipes.

    $pipeHandler = clone $this->pipeHandler;
//    $pipeHandler->registerFallbackHandler ($controller);

    // Setup a rendering context.

    $ctx                      = $this->matisse->createContext ($this->app->tags, $pipeHandler);
    $ctx->condenseLiterals    = $this->app->condenseLiterals;
    $ctx->debugMode           = $this->app->debugMode;
    $ctx->templateDirectories = $this->app->templateDirectories;
    $ctx->presets             = map ($this->app->presets,
      function ($class) { return $this->app->injector->make ($class); });
    $ctx->templatesExt        = '.html';

    // Create a compiled template.

    $page = new Page($ctx);
    return $this->matisse->parse ($src, $ctx, $page);
  }

  function render ($compiled, array $data = [])
  {
    /** @var Page $compiled */
    $compiled->context->dataSources = $data;
    return $this->matisse->render ($compiled);
  }
}
