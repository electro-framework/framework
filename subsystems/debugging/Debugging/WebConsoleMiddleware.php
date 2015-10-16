<?php
namespace Selenia\Debugging;
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\ErrorHandler;
use PhpKit\WebConsole\Panels\HttpRequestPanel;
use PhpKit\WebConsole\WebConsole;
use PhpKit\WebConsole\WebConsoleLogHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class WebConsoleMiddleware implements MiddlewareInterface
{
  private $app;

  private $loader;

  function __construct (Application $app)
  {
    $this->app    = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app = $this->app;
    $debug = $app->debugMode;
    WebConsole::init ($debug);
    WebConsole::registerPanel ('request', new HttpRequestPanel ('Request', 'fa fa-paper-plane'));
    WebConsole::registerPanel ('response', new ConsolePanel ('Response', 'fa fa-file'));
    WebConsole::registerPanel ('routes', new ConsolePanel ('Routes', 'fa fa-location-arrow'));
    WebConsole::registerPanel ('config', new ConsolePanel ('Config.', 'fa fa-cogs'));
    WebConsole::registerPanel ('session', new ConsolePanel ('Session', 'fa fa-user'));
    WebConsole::registerPanel ('DOM', new ConsolePanel ('DOM', 'fa fa-sitemap'));
    WebConsole::registerPanel ('vm', new ConsolePanel ('View Models', 'fa fa-table'));
    WebConsole::registerPanel ('database', new ConsolePanel ('Database', 'fa fa-database'));
//    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));
    ErrorHandler::$appName = $app->appName;

    $response = $next ();

    if ($debug) {
      WebConsole::config ($app);
//      WebConsole::session ()
//                ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
//                ->log ($session);
      if (isset($app->logger))
        $app->logger->pushHandler (new WebConsoleLogHandler(WebConsole::log ()));

      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
//      WebConsole::routes ()->withCaption ('Active Route')->withFilter ($filter, $this->loader->sitePage);
//      WebConsole::response (['Content-Length' => round (ob_get_length () / 1024) . ' KB']);
      WebConsole::response ($response);
    }
//    if (!$this->loader->moduleInstance->isWebService)
      return WebConsole::outputContentViaResponse ($response);

    return $response;
  }
}
