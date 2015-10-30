<?php
namespace Selenia\Http\Controllers;

use Exception;
use PDOStatement;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionObject;
use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\Fatal\DataModelException;
use Selenia\Exceptions\Fatal\FileNotFoundException;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\Flash\FileException;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;
use Selenia\Http\Services\Redirection;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ResponseFactoryInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\ViewInterface;
use Selenia\Matisse\Components\Page;
use Selenia\Matisse\DataRecord;
use Selenia\Matisse\DataSet;
use Selenia\Matisse\DataSource;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Routing\PageRoute;
use Selenia\Routing\Router;
use Selenia\ViewEngine\Engines\MatisseEngine;
use Selenia\ViewEngine\View;
use Zend\Diactoros\Response\HtmlResponse;

class Controller
{
  /**
   * A list of parameter names (inferred from the page definition on the sitemap)
   * and correponding values present on the current URI.
   * @var array
   */
  public $URIParams;
  /**
   * Information about the route associated with this controller.
   * @var PageRoute
   */
  public $activeRoute;
  /**
   * The controller's data sources (view model)
   * The 'default' data source corresponds to the **model**.
   * @var array
   */
  public $dataSources = [];
  /**
   * @var array|null
   */
  public $flashMessage = null;
  /**
   * @var int Maximum number of pages.
   */
  public $max = 1;
  /**
   * @var Array|Object The page's data model.
   */
  public $model;
  /**
   * It's only set when using Matisse.
   * @var Page
   */
  public $page;
  /**
   * @var int Current page number.
   */
  public $pageNumber = 1;
  /**
   * The loader which has loaded this controller.
   * @var Router
   */
  public $router;
  /**
   * An HTML fragment to display a status message or an empty string if no status message exists.
   * @var string
   */
  public $statusMessage = '';
  /**
   * @var View
   */
  public $view;
  /**
   * The current request URI without the page number parameters.
   * This property is useful for databing with the expression {!controller.URI_noPage}.
   * @var string
   */
  protected $URI_noPage;
  /**
   * @var
   */
  protected $app;
  /**
   * Specifies the URL of the index page, to where the browser should naviagate upon the
   * successful insertion / update of records.
   * If not defined on a subclass then the request will redisplay the same page.
   * @var string
   */
  protected $indexPage = null;
  /**
   * If set, defines the page title. It will generate a document `<title>` and it can be used on
   * breadcrumbs.
   * @var string
   */
  protected $pageTitle = null;
  /**
   * @var Redirection
   */
  protected $redirection;
  /**
   * If set to true, the view will be rendered on the POST request without a redirection taking place.
   * @var bool
   */
  protected $renderOnPOST = false;
  /**
   * @var ServerRequestInterface
   */
  protected $request;
  /**
   * @var ResponseInterface
   */
  protected $response;
  /**
   * @var ResponseFactoryInterface
   */
  protected $responseFactory;
  /**
   * @var SessionInterface
   */
  protected $session;
  /**
   * @var string
   */
  protected $viewEngineClass = MatisseEngine::ref;
  /**
   * @var InjectorInterface
   */
  private $injector;

  function __construct (InjectorInterface $injector, Application $app, ResponseFactoryInterface $responseFactory,
                        Redirection $redirection, SessionInterface $session)
  {
    $this->injector        = $injector;
    $this->app             = $app;
    $this->responseFactory = $responseFactory;
    $this->redirection     = $redirection;
    $this->session         = $session;

    // Inject extra dependencies into the subclass' inject method, if it exists.

    if (method_exists ($this, 'inject'))
      $injector->execute ([$this, 'inject']);
  }

  static function ref ()
  {
    return get_called_class ();
  }

  /**
   * Performs the main execution sequence.
   *
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return HtmlResponse
   * @throws FlashMessageException
   * @throws Exception
   */
  final function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->app)
      throw new FatalException("Class <kbd class=type>" . get_class ($this) .
                               "</kbd>'s constructor forgot to call <kbd>parent::__construct()</kbd>");
    $this->request  = $request;
    $this->response = $response;

    // remove page number parameter
    $this->URI_noPage =
      preg_replace ('#&?' . $this->app->pageNumberParam . '=\d*#', '', $this->request->getUri ()->getPath ());
    $this->URI_noPage = preg_replace ('#\?$#', '', $this->URI_noPage);

    $this->initialize (); //custom setup
    $this->setupModel ();
    if ($request->getMethod () == 'POST') {
      $res = $this->processRequest ();
      if ($res)
        return $res;
      if (!$this->renderOnPOST)
        return $this->redirection->refresh ();
    }
    $vm = $this->viewModel ();
    if ($vm)
      array_concat ($this->dataSources, $vm);
    $response = $this->processView ();
    $this->finalize ();
    return $response;
  }

  /**
   * Responds to the standard 'delete' controller action.
   * The default procedure is to delete the object on the database.
   * Override to implement non-standard behaviour.
   * @param null $param
   * @return ResponseInterface
   * @throws FlashMessageException
   * @throws DataModelException
   * @throws Exception
   * @throws FatalException
   */
  function action_delete ($param = null)
  {
    if (!isset($data))
      throw new FlashMessageException('Can\'t delete NULL DataObject.', FlashType::FATAL);
    if ($data instanceof DataObject) {
      if (!isset($data->id) && isset($param)) {
        $data->setPrimaryKeyValue ($param);
        $data->read ();
      }
      $data->delete ();
      return $this->autoRedirect ();
    }
    else throw new FlashMessageException('Can\'t automatically delete object of type ' . gettype ($data),
      FlashType::ERROR);
  }

  /**
   * Allows processing on the server side to occur and redraws the current page.
   * This is useful, for instance, for updating a form by submitting it without actually saving it.
   * The custom processing will usually take place on the render() or the viewModel() methods, but you may also
   * override this method; just make sure you call the inherited one.
   * @param string $param     A JQuery selector for the element that should automatically receive focus after the page
   *                          reloads.
   */
  function action_refresh ($param = null)
  {
    $this->renderOnPOST = true;
    if ($param)
      $this->page->addInlineDeferredScript ("$('$param').focus()");
  }

  /**
   * Respondes to the standard 'submit' controller action.
   * The default procedure is to either call insert() or update().
   * Override to implement non-standard behaviour.
   * @param null $param
   * @throws FlashMessageException
   */
  function action_submit ($param = null)
  {
    $data = $this->model;
    if (!isset($data))
      throw new FlashMessageException('Can\'t insert/update NULL DataObject.', FlashType::ERROR);
    if ($data instanceof DataObject) {
      if ($data->isNew ())
        $this->insertData ();
      else $this->updateData ();
    }
    else throw new FlashMessageException('Can\'t automatically insert/update object of type ' . gettype ($data),
      FlashType::ERROR);
  }

  function beginJSONResponse ()
  {
    header ('Content-Type: application/json');
  }

  function beginXMLResponse ()
  {
    header ('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="utf-8"?>';
  }

  function getRowOffset ()
  {
    return ($this->pageNumber - 1) * $this->app->pageSize;
  }

  function getTitle ()
    // override to return the title of the current page
  {
    return coalesce (
      isset($this->activeRoute) ? $this->activeRoute->title : null,
      $this->pageTitle,
      ''
    );
  }

  /**
   * Loads the record with the id specified on from the request URI into the model object.
   *
   * If the URI parameter is empty, the model is returned unmodified.
   *
   * @param DataObject $model
   * @param string     $param The parameter name. As a convention, it is usually `id`.
   * @return DataObject|false The input model on success, `false` if it was not found.
   */
  function loadRequested (DataObject $model, $param = 'id')
  {
    $id = $this->param ($param);
    if (!$id) return $model;
    $f = $model->find ($id);
    return $f ? $model : false;
  }

  /**
   * Returns the URI parameter with the specified name.
   * @param string $name The parameter name, as specified on the route.
   * @return string
   */
  function param ($name)
  {
    return get ($this->URIParams, $name);
  }

  /**
   * Defines a named data source for the view.
   * @deprecated
   * @see setModel()
   * @param string     $name
   * @param DataSource $data
   * @param boolean    $isDefault
   * @param boolean    $overwrite
   * @throws DataBindingException
   */
  function setDataSource ($name, DataSource $data, $isDefault = false, $overwrite = true)
  {
    $name      = empty($name) ? 'default' : $name;
    $isDefault = $isDefault || $name == 'default';
    if ($isDefault) {
      if (isset($this->dataSources['default']) && !$overwrite)
        throw new DataBindingException(null,
          "The default data source for the page has already been set.\n\nThe current default data source is:\n<pre>$name</pre>");
    }
    $this->dataSources[$name] = $data;
  }

  /**
   * Assigns the specified data to a new (or existing) data source with the
   * specified name.
   * @param string $name The data source name.
   * @param mixed  $data An array, object or <i>null</i>.
   */
  function setViewModel ($name, $data)
  {
    $this->dataSources[$name] = $data;
    return;
    if (!isset($data))
      $this->dataSources[$name] = new DataSet ();
    else if ($data instanceof DataSource)
      $this->dataSources[$name] = $data;
    else if ((is_array ($data) && isset($data[0])) || $data instanceof PDOStatement)
      $this->dataSources[$name] = new DataSet($data);
    else $this->dataSources[$name] = new DataRecord($data);
  }

  /**
   * Allows access to the components tree generated by the parsing process.
   * Component specific initialization can be performed here before the
   * page is rendered.
   * Override to add extra initialization.
   * @param ViewInterface $view
   */
  function setupView (ViewInterface $view)
  {
    $engine = $view->getEngine ();
    if ($engine instanceof MatisseEngine) {
      $this->page        = $view->getCompiledView ();
      $this->page->title = str_replace ('@', $this->getTitle (), $this->app->title);
      $this->page->addScript ("{$this->app->frameworkURI}/js/engine.js");
      if (isset($this->flashMessage))
        $this->displayStatus ($this->flashMessage['type'], $this->flashMessage['message']);
      $this->page->modelDataSource =& getRefAt ($this->dataSources, 'default');
    }
  }

  protected function autoRedirect ()
  {
    if (isset($this->activeRoute))
      return $this->gotoModuleIndex ();
    if (isset($this->indexPage))
      return $this->redirection->to ($this->indexPage);
    throw new FatalException("No index page defined.");
  }

  /**
   * Defines the set of fields which will be fetched to a data object from a POST request.
   * All other values on the request will be ignored.
   * @return array If NULL all the data object's fields fields will be fetched.
   */
  protected function defineDataFields ()
  {
    return null;
  }

  protected function displayStatus ($status, $message)
  {
    if (!is_null ($status)) {
      if ($this->page)
        switch ($status) {
          case FlashType::FATAL:
            @ob_clean ();
            echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></head><body><pre>' .
                 $message .
                 '</pre></body></html>';
            exit;
          case FlashType::ERROR:
            $this->statusMessage =
              '<div id="status" class="alert alert-danger" role="alert">' . $message . '</div>';
            break;
          case FlashType::WARNING:
            $this->statusMessage =
              '<div id="status" class="alert alert-warning" role="alert">' . $message . '</div>';
            break;
          default:
            $this->statusMessage =
              '<div id="status" class="alert alert-info" role="alert">' . $message . '</div>';
        }
      else echo $message;
    }
  }

  /**
   * Invokes the right controller method in response to the POST request's specified action.
   * @param DataObject $data
   * @return ResponseInterface|null
   * @throws FlashMessageException
   * @throws FileException
   */
  protected function doFormAction (DataObject $data = null)
  {
    if (count ($_POST) == 0 && count ($_FILES) == 0)
      throw new FileException(FileException::FILE_TOO_BIG, ini_get ('upload_max_filesize'));
    $this->getActionAndParam ($action, $param);
    $class = new ReflectionObject($this);
    try {
      $method = $class->getMethod ('action_' . $action);
    } catch (ReflectionException $e) {
      throw new FlashMessageException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
        FlashType::ERROR);
    }
    return $method->invoke ($this, $data, $param);
  }

  /**
   * Override to do something after the page has been rendered.
   */
  protected function finalize () {
    // no op
  }

  protected function getActionAndParam (&$action, &$param)
  {
    $action = get ($_REQUEST, '_action', '');
    if (preg_match ('#(\w*):(.*)#', $action, $match)) {
      $action = $match[1];
      $param  = $match[2];
    }
    else $param = null;
  }

  protected function gotoModuleIndex ()
  {
    if (isset($this->activeRoute->indexURL))
      return $this->redirection->to ($this->activeRoute->indexURL);
    else {
      /** @var PageRoute $index */
      $index = $this->activeRoute->getIndex ();
      if (!$index)
        return $this->redirection->home ();
      return $this->redirection->to ($index->evalURI ($this->URIParams));
    }
  }

  /**
   * Initializes the controller.
   * Override to implement initialization code that should run before all other processing on the controller.
   * Make sure to always call the parent function.
   * @global Application $application
   */
  protected function initialize ()
  {
    if (isset($this->app->routingMap)) {
      $this->activeRoute = $this->router->activeRoute;
      $this->URIParams   = $this->activeRoute->getURIParams ();
    }
  }

  /**
   * Respondes to the standard 'submit' controller action when a primary key value is not present on the request.
   * The default procedure is to create a new record on the database.
   * Override to implement non-standard behaviour.
   */
  protected function insertData ()
  {
    $data = $this->model;
    if ($data instanceof DataObject)
      $data->insert ();
  }

  /**
   * Should be overriden when submitted data should be preprocessed.
   * @param DataObject $data
   */
  protected function interceptFormData (DataObject $data)
  {
    if (isset($this->URIParams)) {
      extendNonEmpty ($data, $this->URIParams);
    }
  }

  /**
   * Override to return the main model for the controller / view.
   *
   * > This model will be available on both GET and POST requests and it will also be set as the default data source
   * for the view model.
   *
   * @return DataObject|PDOStatement|array
   */
  protected function model ()
  {
    return null;
  }

  protected function paginate (array &$data, $pageSize = 0)
  {
    if (!$pageSize)
      $pageSize = $this->app->pageSize;
    $this->pageNumber = get ($_REQUEST, $this->app->pageNumberParam, 1);
    $count            = count ($data);
    if ($count > $pageSize) {
      $this->max = ceil ($count / $pageSize);
      if ($this->pageNumber > 1) {
        $skip = $this->getRowOffset ();
        array_splice ($data, 0, $skip);
      }
      array_splice ($data, $pageSize);
    }
  }

  /**
   * Implements page specific action processing, in response to a POST request.
   * To implement standard behavior, override and make a call to $this->processForm($data),
   * where $data is the data object to be processed.
   * If you use the standard dataItem property, there is no need to override this method.
   * @return null|ResponseInterface
   */
  protected function processRequest ()
  {
    return $this->processForm ($this->model);
  }

  /**
   * Performs all processing related to the view generation.
   * @return ResponseInterface
   * @throws FileNotFoundException
   */
  protected function processView ()
  {
    $this->setupBaseViewModel ();
    // Normal page rendering (not a login form).

    $this->view = $this->injector->make ('Selenia\Interfaces\ViewInterface');
    ob_start ();
    $view    = $this->render ();
    $content = ob_get_clean ();
    if (!$view) {
      /** @var ViewInterface $view */
      $view = $this->view
        ->setEngine ($this->viewEngineClass)
        ->loadFromString ($content);
    }
    $this->setupView ($view);
    /** @var ViewInterface $view */
    $output = $view->render ($this->dataSources);
    $this->response->getBody ()->write ($output);
    return $this->response;
  }

  /**
   * Allows subclasses to generate the view's markup dinamically.
   * If not overriden, the default behaviour is to load the view from an external file, if one is defined on the active
   * route. If not, no output is generated.
   * @return ViewInterface|null If `null`, the framework assumes the content has been output to PHP's output buffer.
   */
  protected function render ()
  {
    if (isset($this->router)) {
      if (isset($this->activeRoute->view)) {
        return $this->view->loadFromFile ($this->activeRoute->view);
      }
    }
    return null;
  }

  protected final function setStatus ($type, $msg)
  {
    throw new FlashMessageException ($msg, $type);
  }

  /**
   * Sets up a set of standard data sources which are available for databinding on all the application's views.
   * When overriden the parent class method should always be called.
   */
  protected function setupBaseViewModel ()
  {
    $this->setViewModel ('application', $this->app);
    if (isset($this->session)) {
      $this->setViewModel ('user', $this->session->user ());
      $this->setViewModel ('sessionInfo', $this->session);
    }
    $this->setViewModel ('controller', $this);
    $this->setViewModel ('request', $this->request->getQueryParams ());
    if (isset($this->activeRoute)) {
      $this->setViewModel ('sitePage', $this->activeRoute);
      $this->setViewModel ('config', $this->activeRoute->config);
    }
    if (isset($this->router))
      $this->setViewModel ('module', $this->router->moduleInfo);
    $this->setViewModel ('languages', isset($this->langInfo) ? array_values ($this->langInfo) : null);
    $this->setViewModel ("URIParams", $this->URIParams);
  }

  /**
   * Sets up a page specific data model for use on the processRequest() phase and/or on the processView() phase.
   *
   * Override this if you want to manually specify the model.
   * The model is saved on `$this->model` and on the 'default' data source.
   */
  protected function setupModel ()
  {
    $this->dataSources['default'] = $this->model = $this->model ();
  }

  /**
   * Responds to the standard 'submit' controller action when a primary key value is present on the request.
   * The default procedure is to save the object to the database.
   * Override to implement non-standard behaviour.
   * @throws Exception
   */
  protected function updateData ()
  {
    $data = $this->model;
    if ($data instanceof DataObject)
      $data->update ();
  }

  /**
   * Override to set addition models for the controller / view.
   *
   * > View models are available only on GET requests.
   *
   * @return array|void If you return an array, the keys will be set as datasource names.
   */
  protected function viewModel ()
  {
    //Override.
    return null;
  }

  /**
   * Responds to a POST request.
   * @param DataObject $data
   * @return null|ResponseInterface
   */
  private function processForm (DataObject $data = null)
  {
    if (isset($data)) {
      $data->loadFromHttpRequest ($this->defineDataFields ());
      $this->interceptFormData ($data);
    }
    return $this->doFormAction ($data);
  }

}
