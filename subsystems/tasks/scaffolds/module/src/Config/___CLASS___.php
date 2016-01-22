<?php
namespace ___NAMESPACE___\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;

class ___CLASS___Module implements RequestHandlerInterface, ModuleInterface, NavigationProviderInterface
{
  /** @var RouterInterface */
  private $router;

  /*
   * Uncomment `registerRouter ($this)` on method `configure()` to enable this.
   * Remove this (and the `implements RequestHandlerInterface`) if you don't need it.
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $this->router
      ->set ([
        // '.' => Some::class,
      ])
      ->__invoke ($request, $response, $next);
  }

  /*
   * Uncomment, below, the lines that enable the features you need.
   */
  function configure (ModuleServices $module, RouterInterface $router)
  {
    $this->router = $router;
    $module
      //->publishPublicDirAs ('modules/___MODULE_PATH___')
      //->provideMacros ()
      //->provideViews ()
      //->registerRouter ($this)
      //->provideNavigation ($this)
      ->setDefaultConfig ([
        'main' => [
          'name'    => 'yourapp',       // session cookie name
          'appName' => 'Your App',      // default page title; also displayed on title bar (optional)
          'title'   => '@ - Your App',  // @ = page title
        ],
      ]);
  }

  /*
   * Uncomment `provideNavigation ($this)` on method `configure()` to enable this.
   * Remove this (and the `implements NavigationProviderInterface`) if you don't need it.
   */
  function defineNavigation (NavigationInterface $navigation)
  {
    $navigation->add ([
      '' => $navigation
        ->link ()
        ->id ('home')
        ->title ('Home')
        ->links ([
          // Root navigation links go here
        ]),
    ]);
  }

}
