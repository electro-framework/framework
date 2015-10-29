<?php
namespace Selenia\Matisse\Config;

use Selenia\Application;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\DefaultPipes;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Matisse\PipeHandler;

class MatisseModule implements ServiceProviderInterface, ModuleInterface
{
  function boot (Application $app = null, PipeHandler $pipeHandler = null)
  {
    $pipeHandler->registerPipes (new DefaultPipes);
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
    $injector->share (PipeHandler::ref);
//    $injector->share (new MatisseEngine);
  }
}
