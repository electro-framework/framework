<?php
namespace ___NAMESPACE___\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\ModuleInterface;

class ___CLASS___ implements ModuleInterface
{
  /*
   * Remove, below, what you don't need; it will improve performance.
   */
  function configure (ModuleServices $module, Application $app)
  {
    $app->name    = 'yourapp';      // session cookie name
    $app->appName = 'Your App';     // default page title; also displayed on title bar (optional)
    $app->title   = '@ - Your App'; // @ = page title
    $module
      ->provideMacros ()
      ->provideViews ()
      ->registerRouter (Routes::class)
      ->registerNavigation (Navigation::class);
  }

}
