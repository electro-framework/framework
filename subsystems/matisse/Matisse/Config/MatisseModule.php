<?php
namespace Selenia\ViewEngine\Config;

use Selenia\Application;
use Selenia\DefaultFilters;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Lib\FilterHandler;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Services\AssetsService;
use Selenia\Matisse\Services\BlocksService;

class MatisseModule implements ServiceProviderInterface, ModuleInterface
{
  function boot (Application $app = null, FilterHandler $filterHandler = null)
  {
    $app->condenseLiterals = !$app->debugMode;
    $app->compressOutput   = !$app->debugMode;
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->prepare (DocumentContext::class,
        function (DocumentContext $ctx, Application $app, ViewServiceInterface $viewService) use ($injector) {
          $ctx->registerTags ($app->tags);
          $ctx->setFilterHandler ($filterHandler = new FilterHandler);
          $filterHandler->registerFilters (new DefaultFilters ($app));
          $ctx->condenseLiterals     = $app->condenseLiterals;
          $ctx->debugMode            = $app->debugMode;
          $ctx->macrosDirectories    = $app->macrosDirectories;
          $ctx->controllers          = $app->controllers;
          $ctx->controllerNamespaces = $app->controllerNamespaces;
          $ctx->presets              = map ($app->presets,
            function ($class) use ($app) { return $app->injector->make ($class); });
          $ctx->getMacrosService ()
            ->macrosExt              = '.html';
          $ctx->injector             = $injector;
          $ctx->viewService          = $viewService;
          return $ctx;
        })
      ->share (DocumentContext::class)
      ->share (new AssetsService())
      ->share (new BlocksService());
  }

}
