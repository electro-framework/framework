<?php
namespace Selenia\Debugging;
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\Panels\PSR7RequestPanel;
use PhpKit\WebConsole\Panels\PSR7ResponsePanel;
use PhpKit\WebConsole\WebConsole;
use PhpKit\WebConsole\WebConsoleLogHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Router;

/**
 *
 */
class WebConsoleMiddleware implements MiddlewareInterface
{
  private $app;
  private $injector;
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (Application $app, InjectorInterface $injector, SessionInterface $session)
  {
    $this->app      = $app;
    $this->injector = $injector;
    $this->session  = $session;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app = $this->app;
    WebConsole::registerPanel ('request', new PSR7RequestPanel ('Request', 'fa fa-paper-plane'));
    WebConsole::registerPanel ('response', new PSR7ResponsePanel ('Response', 'fa fa-file'));
    WebConsole::registerPanel ('routes', new ConsolePanel ('Routes', 'fa fa-location-arrow'));
    WebConsole::registerPanel ('config', new ConsolePanel ('Config.', 'fa fa-cogs'));
    WebConsole::registerPanel ('session', new ConsolePanel ('Session', 'fa fa-user'));
    WebConsole::registerPanel ('DOM', new ConsolePanel ('DOM', 'fa fa-sitemap'));
    WebConsole::registerPanel ('vm', new ConsolePanel ('View Models', 'fa fa-table'));
    WebConsole::registerPanel ('database', new ConsolePanel ('Database', 'fa fa-database'));
//    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));

    $response = $next ();

    // Redirect logger to Inspector panel
    if (isset($app->logger))
      $app->logger->pushHandler (new WebConsoleLogHandler(WebConsole::log ()));

    $response->getBody ()->rewind ();

    // Request panel
    WebConsole::request ()->setRequest ($request);

    // Response panel
    WebConsole::response ()->setResponse ($response);

    // Config. panel
    WebConsole::config ($app);

    // Session panel
    WebConsole::session ()
              ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
              ->log ($this->session);

    // Routes panel
    /** @var Router $router */
    $router = $this->injector->make ('Selenia\Router');
    if (isset($router->controller)) {

      // DOM panel
      $insp = $router->controller->page->inspect (true);
      WebConsole::DOM ()->write ($insp);
//      $filter = function ($k, $v) { return $k !== 'parent' && $k !== 'page'; };
//      WebConsole::DOM ()->withFilter($filter, $controller->page);

      // View Models panel
      WebConsole::vm ()->log ($router->controller->context->dataSources);
    }

    return WebConsole::outputContentViaResponse ($request, $response, true);
  }
}
