<?php
namespace Selenia\ViewEngine\Config;

use Selenia\Application;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\DefaultPipes;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Matisse\Lib\PipeHandler;
use Selenia\ViewEngine\Engines\MatisseEngine;
use Selenia\ViewEngine\View;

class ViewEngineModule implements ServiceProviderInterface, ModuleInterface
{
  function boot (Application $app = null, PipeHandler $pipeHandler = null)
  {
    $pipeHandler->registerPipes (new DefaultPipes ($app));
    $app->condenseLiterals = !$app->debugMode;
    $app->compressOutput   = !$app->debugMode;
  }

  function configure (ModuleServices $module)
  {
//    $module
//      ->setDefaultConfig ([
//        'selenia/view-engine' => (new ViewEngineSettings)
//      ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (new PipeHandler);
//    $pipeHandler = $injector->make(PipeHandler::class);
    $injector->delegate (ViewInterface::class, function (View $view) {
      $view->register (MatisseEngine::class, '/\.html$/');
      return $view;
    });
  }

}
