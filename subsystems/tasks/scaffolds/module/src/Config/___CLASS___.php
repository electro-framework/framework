<?php
namespace ___NAMESPACE___\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Application;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;

class ___CLASS___ implements
  ModuleInterface
  , RequestHandlerInterface       // remove this if this module will not provide routing
  , NavigationProviderInterface   // remove this if this module will not provide navigation
{
  /** @var RouterInterface */
  private $router;

  /*
   * Remove this method if this module will not provide routing.
   * Also remove:
   *   * the line with `RequestHandlerInterface` on the `implements` list above
   *   * the `private $router` property
   *   * the following items on method `configure()`:
   *     * `RouterInterface $router` method parameter
   *     * `$this->router = $router;`
   *     * `->registerRouter ($this)`
   *
   * Either way, you should also remove this comment block when it's no longer needed.
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
   * Remove, below, what you don't need; it will improve performance and it's easy to add it back later by using your
   * IDE's auto-completion (ex. by pressing Ctrl+Space after a ->).
   */
  function configure (ModuleServices $module, RouterInterface $router, Application $app)
  {
    $this->router = $router;
    $app->name    = 'yourapp';      // session cookie name
    $app->appName = 'Your App';     // default page title; also displayed on title bar (optional)
    $app->title   = '@ - Your App'; // @ = page title
    $module
      ->publishPublicDirAs ('modules/___MODULE_PATH___')
      ->provideMacros ()
      ->provideViews ()
      ->registerRouter ($this)
      ->registerNavigation ($this);
  }

  /*
   * Remove this method if this module will not provide navigation.
   * Also remove:
   *   * the line with `NavigationProviderInterface` on the `implements` list above
   *   * the following items on method `configure()`:
   *     * `->provideNavigation ($this)`
   *
   * Either way, you should also remove this comment block when it's no longer needed.
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
