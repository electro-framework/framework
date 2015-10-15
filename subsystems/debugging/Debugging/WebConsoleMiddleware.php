<?php
namespace Selenia\Subsystems\Http\Middleware;
use Impactwave\WebConsole\ConsolePanel;
use Impactwave\WebConsole\Panels\HttpRequestPanel;
use Impactwave\WebConsole\WebConsole;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\ModuleLoader;

/**
 *
 */
class WebConsoleMiddleware implements MiddlewareInterface
{
  private $app;

  private $loader;

  function __construct (Application $app, ModuleLoader $loader)
  {
    $this->app    = $app;
    $this->loader = $loader;
  }

  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
    $debug = $this->app->debugMode;
    WebConsole::init ($debug);
    WebConsole::registerPanel ('request', new HttpRequestPanel ('Request', 'fa fa-paper-plane'));
    WebConsole::registerPanel ('response', new ConsolePanel ('Response', 'fa fa-file'));
    WebConsole::registerPanel ('routes', new ConsolePanel ('Routes', 'fa fa-location-arrow'));
    WebConsole::registerPanel ('session', new ConsolePanel ('Session', 'fa fa-user'));
    WebConsole::registerPanel ('database', new ConsolePanel ('Database', 'fa fa-database'));
    WebConsole::registerPanel ('DOM', new ConsolePanel ('DOM', 'fa fa-sitemap'));
    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));

    $response = $next ($request, $response);

    if ($debug) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withCaption ('Active Route')->withFilter ($filter, $this->loader->sitePage);
      WebConsole::response ($response);
    }
    if (!$this->loader->moduleInstance->isWebService)
      return WebConsole::outputContentViaResponse ($response);

    return $response;
  }
}
