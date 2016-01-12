<?php
namespace Selenia\ViewEngine\Engines;

use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Views\ViewEngineInterface;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Lib\PipeHandler;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Parser;

class MatisseEngine implements ViewEngineInterface
{
  /**
   * The injector allows the creation of components with yet unknown dependencies.
   *
   * @var InjectorInterface
   */
  public $injector;

  /**
   * @var Application
   */
  private $app;
  /**
   * @var Context
   */
  private $context;
  /**
   * @var PipeHandler
   */
  private $pipeHandler;

  function __construct (PipeHandler $pipeHandler, Application $app, InjectorInterface $injector)
  {
    $this->pipeHandler = $pipeHandler;
    $this->app         = $app;
    $this->injector    = $injector;
  }

  function compile ($src)
  {
    // Setup Matisse pipes.

    $pipeHandler = clone $this->pipeHandler;
//    $pipeHandler->registerFallbackHandler ($controller);

    if ($this->context)
      $ctx = $this->context;
    else {
      // Setup a rendering context.

      $ctx = new Context;
      $ctx->registerTags ($this->app->tags);
      $ctx->setPipeHandler ($pipeHandler);

      $ctx->condenseLiterals  = $this->app->condenseLiterals;
      $ctx->debugMode         = $this->app->debugMode;
      $ctx->macrosDirectories = $this->app->macrosDirectories;
      $ctx->presets           = map ($this->app->presets,
        function ($class) { return $this->app->injector->make ($class); });
      $ctx->macrosExt         = '.html';
      $ctx->injector          = $this->injector;
    }

    // Create a compiled template.

    $root = new DocumentFragment;
    $root->setContext ($ctx);

    $parser = new Parser;
    $parser->parse ($src, $root);
    return $root;
  }

  function configure ($options)
  {
    if (!$options instanceof Context)
      throw new \InvalidArgumentException ("The argument must be an instance of " . formatClassName (Context::class));
    $this->context = $options;
  }

  function render ($compiled, $data = null)
  {
    /** @var DocumentFragment $compiled */
    $compiled->context->viewModel = $data;
    return $compiled->getRenderedComponent ();
  }

}
