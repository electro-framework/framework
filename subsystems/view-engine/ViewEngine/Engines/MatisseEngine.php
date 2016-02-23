<?php
namespace Selenia\ViewEngine\Engines;

use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Views\ViewEngineInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Exceptions\MatisseException;
use Selenia\Matisse\Lib\PipeHandler;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Parser;

class MatisseEngine implements ViewEngineInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * The current rendering context.
   *
   * @var Context
   */
  private $context;
  /**
   * The injector allows the creation of components with yet unknown dependencies.
   *
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var PipeHandler
   */
  private $pipeHandler;
  /**
   * @var ViewServiceInterface
   */
  private $view;

  function __construct (PipeHandler $pipeHandler, Application $app, InjectorInterface $injector,
                        ViewServiceInterface $view, Context $context)
  {
    $this->pipeHandler = $pipeHandler;
    $this->app         = $app;
    $this->injector    = $injector;
    $this->view        = $view; // The view is always the owner if this engine, as long as the parameter is called $view
    $this->context     = $context;
  }

  function compile ($src)
  {
    if (!$this->context)
      throw new MatisseException ("No rendering context is set");

    // Create a compiled template.

    $root = new DocumentFragment;
    $root->setContext ($this->context);

    $parser = new Parser;
    $parser->parse ($src, $root);
    return $root;
  }

  function configure ($options)
  {
//    if (!$options instanceof Context)
//      throw new \InvalidArgumentException ("The argument must be an instance of " . formatClassName (Context::class));
//    $this->context = $options;
  }

  function render ($compiled, $data = null)
  {
    /** @var DocumentFragment $compiled */
    if (isset($data)) {
      $previous                     = $compiled->context->viewModel;
      $compiled->context->viewModel = $data;
      $out                          = $compiled->getRenderedComponent ();
      $compiled->context->viewModel = $previous;
      return $out;
    }
    return $compiled->getRenderedComponent ();
  }

}
