<?php

namespace Electro\ViewEngine\Lib;

use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Interop\ViewModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PageHandler implements RequestHandlerInterface
{
  /**
   * @var string The Content-Type header for the generated HTTP response.
   */
  protected $contentType = 'text/html';
  /**
   * @var ViewEngineInterface|string The view engine's class name or an instance of it.
   */
  protected $engine;
  /**
   * @var MiddlewareStackInterface
   */
  protected $middleware;
  /**
   * @var string An inline template. When using this, you must also set {@see $engine}.
   */
  protected $template;
  /**
   * @var string The filesystem path of a template file to be rendered.
   */
  protected $templateUrl;
  /**
   * @var ViewServiceInterface
   */
  private $viewService;

  public function __construct (ViewServiceInterface $viewService, MiddlewareStackInterface $middlewareStack)
  {
    $this->middleware  = $middlewareStack;
    $this->viewService = $viewService;
    $this->setup ();
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $stack = $this->middleware;
    return $stack ($request, $response, $next);
  }

  public final function render (ServerRequestInterface $request, ResponseInterface $response)
  {
    if ($this->templateUrl)
      $view = $this->viewService->loadFromFile ($this->templateUrl);
    elseif ($this->template)
      $view = $this->viewService->loadFromString ($this->templateUrl, $this->engine);
    else return $response;
    $viewModel = new ViewModel;
    $this->viewModel ($viewModel, $request);
    $this->viewService->on (ViewServiceInterface::EVENT_RENDER,
      function (ViewInterface $subview, ViewModel $viewModel) use ($view, $request) {

      });
    $response->getBody ()->write ($view->render ($viewModel));
    return $response->withHeader ('Content-Type', $this->contentType);
  }

  /**
   * Override to initialize the internal middleware.
   *
   * <p>You should always call `parent::setup()`, either **before** or **after** adding your middleware.
   * - If you call it before, your middleware will run **after** the view is rendered.
   * - If you call it after, your middleware will run **before** the view is rendered.
   *
   * <p>To add middleware, call `$this->middleware->add()`.
   */
  protected function setup ()
  {
    $this->middleware->add (fn ([$this, 'render']), 'render');
  }

  /**
   * Allows subclasses to define the view's view model.
   * <p>You do not need to call `parent::viewModel()`. If you need to
   *
   * @param ViewModel              $viewModel
   * @param ServerRequestInterface $request
   */
  protected function viewModel (ViewModel $viewModel, ServerRequestInterface $request)
  {

  }

}
