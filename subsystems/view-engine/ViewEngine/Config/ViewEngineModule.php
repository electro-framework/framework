<?php
namespace Selenia\ViewEngine\Config;

use Selenia\Application;
use Selenia\DefaultFilters;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Lib\FilterHandler;
use Selenia\Matisse\Parser\Context;
use Selenia\ViewEngine\Engines\MatisseEngine;
use Selenia\ViewEngine\View;
use Selenia\ViewEngine\ViewService;

class ViewEngineModule implements ServiceProviderInterface, ModuleInterface
{
  function boot (Application $app = null, FilterHandler $filterHandler = null)
  {
    $app->condenseLiterals = !$app->debugMode;
    $app->compressOutput   = !$app->debugMode;
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->alias (ViewInterface::class, View::class)//note: this is not used by ViewService.
      ->alias (ViewServiceInterface::class, ViewService::class)
      ->share (ViewServiceInterface::class)
      // Register the built-in view engines:
      ->prepare (ViewServiceInterface::class, function (ViewServiceInterface $viewService) {
        $viewService->register (MatisseEngine::class, '/\.html$/');
      })
      ->delegate (Context::class,
        function (Application $app, ViewServiceInterface $viewService) use ($injector) {
          $ctx = new Context;
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
          $ctx->macrosExt            = '.html';
          $ctx->injector             = $injector;
          $ctx->viewService          = $viewService;
          return $ctx;
        })
      ->share (Context::class);
  }

}
