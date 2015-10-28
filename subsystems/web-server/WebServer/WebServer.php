<?php
namespace Selenia\WebServer;
use Selenia\Interfaces\InjectorInterface;

/**
 * Responds to an HTTP request made to the web application by loading and running all the framework subsystems required
 * to handle that request.
 */
class WebServer
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  /**
   * @param InjectorInterface $injector
   */
  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * Runs the web server's request handler.
   * @param string $rootDir The application's root directory path.
   */
  function run ($rootDir)
  {

    $routeLoader = new RouteLoader();
    $routes      = $routeLoader->loadFromXml (CONTROLLER_ROUTES);
    $router      = new Router($routes);

    $requestDetector = new RequestDetector();
    $request         = $requestDetector->detectFromSuperglobal ($_SERVER);

    $requestUri    = $request->getUri ();
    $requestMethod = strtolower ($request->getMethod ());

    $this->injector->share ($request);

    try {
      if (!$controllerClass = $router->route ($requestUri, $requestMethod)) {
        throw new NoRouteMatchException();
      }

      $controller         = $this->injector->make ($controllerClass);
      $callableController = [$controller, $requestMethod];

      if (!is_callable ($callableController)) {
        throw new MethodNotAllowedException();
      }
      else {
        $callableController();
      }

    } catch (NoRouteMatchException $e) {
      // send 404 response
    } catch (MethodNotAllowedException $e) {
      // send 405 response
    } catch (Exception $e) {
      // send 500 response
    }
  }

}
