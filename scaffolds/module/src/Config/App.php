<?php
namespace ___NAMESPACE___\Config;

use App\Controllers\Welcome;

class App
{
  const ref = __CLASS__;

  static function routes ()
  {
    return [

      // Example route implementing a self-contained component-like controller.

      PageRoute ([
        'title'      => 'Example Route 1',
        'URI'        => 'route1',
        'controller' => Welcome::ref,
      ]),

      // Example route using an automatic controller and an external view.

      PageRoute ([
        'title'          => 'Example Route 2',
        'URI'            => 'example',
        'module'         => 'ExampleModule',
        'view'           => 'index.html',
        'autoController' => true,
      ]),

    ];
  }
}
