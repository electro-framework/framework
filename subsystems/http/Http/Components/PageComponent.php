<?php
namespace Selenia\Http\Components;

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
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\ResponseFactoryInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Matisse\Components\Page;
use Selenia\Matisse\PipeHandler;
use Selenia\Routing\Router;
use Selenia\ViewEngine\Engines\MatisseEngine;
use Selenia\ViewEngine\View;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * The base class for components that are web pages.
 *
 * ### Notes:
 * - subclasses that define an inject() method will have that methoc called and dependency-injected upon instantiation.
 */
class PageComponent implements RequestHandlerInterface
{
  /**
   * @var Application
   */
  public $app;
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
   * @var array|Object|DataObject The page's data model.
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
   * @var ServerRequestInterface
   */
  public $request;
  /**
   * The loader which has loaded this controller.
   * @var Router
   */
  public $router;
  /**
   * @var SessionInterface
   */
  public $session;
  /**
   * An HTML fragment to display a status message or an empty string if no status message exists.
   * @var string
   */
  public $statusMessage = '';
  /**
   * Set this to automatically load a view from the specified external template.
   * @var string
   */
  public $templateUrl;
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
   * @var RedirectionInterface
   */
  protected $redirection;
  /**
   * If set to true, the view will be rendered on the POST request without a redirection taking place.
   * @var bool
   */
  protected $renderOnAction = false;
  /**
   * @var ResponseInterface
   */
  protected $response;
  /**
   * @var ResponseFactoryInterface
   */
  protected $responseFactory;
  /**
   * @var string
   */
  protected $viewEngineClass = MatisseEngine::class;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * Values to be automatically merged into the view model.
   * @var array
   */
  private $presets = [];

  function __construct (InjectorInterface $injector, Application $app, ResponseFactoryInterface $responseFactory,
                        RedirectionInterface $redirection, SessionInterface $session, PipeHandler $pipeHandler)
  {
    $this->injector        = $injector;
    $this->app             = $app;
    $this->responseFactory = $responseFactory;
    $this->redirection     = $redirection;
    $this->session         = $session;
    $pipeHandler->registerFallbackHandler ($this);

    // Inject extra dependencies into the subclass' inject method, if it exists.

    if (method_exists ($this, 'inject'))
      $injector->execute ([$this, 'inject']);
  }

  /**
   * Performs the main execution sequence.
   *
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return HtmlResponse
   * @throws FatalException
   * @throws FileException
   * @throws FlashMessageException
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->app)
      throw new FatalException("Class <kbd class=type>" . get_class ($this) .
                               "</kbd>'s constructor forgot to call <kbd>parent::__construct()</kbd>");
    $this->request  = $request;
    $this->response = $response;
    $this->redirection->setRequest ($request);

    // remove page number parameter
    $this->URI_noPage =
      preg_replace ('#&?' . $this->app->pageNumberParam . '=\d*#', '', $this->request->getUri ()->getPath ());
    $this->URI_noPage = preg_replace ('#\?$#', '', $this->URI_noPage);

    $this->initialize (); //custom setup

    $this->model = $this->model ();
    $model       =& $this->model;
    $this->mergeIntoModel ($model, $request->getAttributes ());
    switch ($this->request->getMethod ()) {
      case 'GET':
        if ($model) array_mergeInto ($model, $this->presets);
        $this->mergeIntoModel ($model, $this->session->getOldInput ());
        break;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'POST':
        if ($this->request->getHeaderLine ('Content-Type') == 'application/x-www-form-urlencoded')
          $this->mergeIntoModel ($model, $this->request->getParsedBody ());
      default:
        $res = $this->doFormAction ();
        if (!$res && !$this->renderOnAction)
          $res = $this->autoRedirect ();
        $this->finalize ();
        return $res;
    }
    $this->viewModel ();
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
    if (!isset($this->model))
      throw new FlashMessageException('Can\'t delete NULL DataObject.', FlashType::ERROR);
    $data = $this->model;
    if ($data instanceof DataObject) {
      if (!isset($this->model->id) && isset($param)) {
        $data->setPrimaryKeyValue ($param);
        $data->read ();
      }
      $data->delete ();
      return $this->autoRedirect ();
    }
    else throw new FlashMessageException(sprintf ('Can\'t automatically delete object of type <kbd>%s</kbd>',
      gettype ($data)),
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
    $this->renderOnAction = true;
    if ($param)
      $this->page->addInlineDeferredScript ("$('$param').focus()");
  }

  /**
   * Responds to the standard 'submit' controller action.
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
        $this->insertData ($data);
      else $this->updateData ($data);
    }
    else throw new FlashMessageException('Can\'t automatically insert/update object of type ' . gettype ($data),
      FlashType::ERROR);
    // No return value means: auto-redirect
  }

  function getRowOffset ()
  {
    return ($this->pageNumber - 1) * $this->app->pageSize;
  }

  function getTitle ()
    // override to return the title of the current page
  {
    return coalesce (
//      isset($this->activeRoute) ? $this->activeRoute->title : null,
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
    $id = $this->request->getAttribute ("@$param");
    if (!$id) return $model;
    $f = $model->find ($id);
    return $f ? $model : false;
  }

  /**
   * Merges data into the view model.
   * @param array $data
   */
  function preset (array $data)
  {
    $this->presets = $data;
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
      $flashMessage = $this->session->getFlashMessage ();
      if ($flashMessage)
        $this->displayStatus ($flashMessage['type'], $flashMessage['message']);
      $this->page->contextualModel = $this;
    }
  }

  protected function autoRedirect ()
  {
    //TODO: redirect to index page

    if (isset($this->indexPage))
      return $this->redirection->to ($this->indexPage);

//    $index = $this->activeRoute->getIndex ();
//    if (!$index)
//      return $this->redirection->home ();
//    return $this->redirection->to ($index->evalURI ($this->URIParams));

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
              '<div id="status" class="alert alert-danger" role="alert"><div>' . $message . '</div></div>';
            break;
          case FlashType::WARNING:
            $this->statusMessage =
              '<div id="status" class="alert alert-warning" role="alert"><div>' . $message . '</div></div>';
            break;
          default:
            $this->statusMessage =
              '<div id="status" class="alert alert-info" role="alert"><div>' . $message . '</div></div>';
        }
      else echo $message;
    }
  }

  /**
   * Invokes the right controller method in response to the POST request's specified action.
   * @return ResponseInterface|null
   * @throws FlashMessageException
   * @throws FileException
   */
  protected function doFormAction ()
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
    return $method->invoke ($this, $param);
  }

  /**
   * Override to do something after the page has been rendered.
   */
  protected function finalize ()
  {
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

  /**
   * Initializes the controller.
   * Override to implement initialization code that should run before all other processing on the controller.
   * Make sure to always call the parent function.
   */
  protected function initialize ()
  {
  }

  /**
   * Responds to the standard 'submit' controller action when a primary key value is not present on the request.
   * The default procedure is to create a new record on the database if the model is an ORM model.
   * Override to implement your own saving algorithm.
   * @param $model
   * @throws FatalException
   */
  protected function insertData ($model)
  {
    if ($model instanceof DataObject)
      $model->insert ();
    else throw new FatalException (sprintf ("Don't know how to save a model of type <kbd class=type>%s</kbd>.<p>You should override <kbd>insertData()</kbd>.",
      is_object ($model) ? get_class ($model) : gettype ($model)));
  }

  /**
   * @param array|object|DataObject $model
   * @param array|null              $data If `null` nothihg happens.
   * @throws FatalException
   */
  protected function mergeIntoModel (& $model, array $data = null)
  {
    if (is_null ($data) || is_null ($model)) return;
    if ($model instanceof DataObject)
      $model->safeLoadFrom ($data, $this->defineDataFields ());
    else if (is_array ($model))
      array_mergeExisting ($model, array_normalizeEmptyValues ($data));
    else if (is_object ($model))
      extendExisting ($model, array_normalizeEmptyValues ($data));
    else throw new FatalException (sprintf ("Can't merge data into a model of type <kbd>11%s</kbd>", gettype ($model)));
  }

  /**
   * Override to return the main model for the controller / view.
   *
   * > This model will be available on all request methods (GET, POST, etc) and it will also be set as the 'model' view
   * model.
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
   * Allows subclasses to generate the view's markup dinamically.
   * If not overriden, the default behaviour is to load the view from an external file, if one is defined on
   * `templateUrl`. If not, no output is generated.
   * @return ViewInterface|null If `null`, the framework assumes the content has been output to PHP's output buffer.
   */
  protected function render ()
  {
    if ($this->templateUrl)
      return $this->view->loadFromFile ($this->templateUrl);
    return null;
  }

  /**
   * Responds to the standard 'submit' controller action when a primary key value is present on the request.
   * The default procedure is to create a new record on the database if the model is an ORM model.
   * Override to implement your own saving algorithm.
   * @param $model
   * @throws FatalException
   */
  protected function updateData ($model)
  {
    if ($model instanceof DataObject)
      $model->update ();
    else throw new FatalException (sprintf ("Don't know how to save a model of type <kbd class=type>%s</kbd>.<p>You should override <kbd>updateData()</kbd>.",
      is_object ($model) ? get_class ($model) : gettype ($model)));
  }

  /**
   * Override to set data on the controller's view model.
   *
   * Data will usually be set on the controller instance.
   * <p>Ex:
   * > `$this->data = ...`
   *
   * <p>Note:
   * > View models are available only on GET requests.
   */
  protected function viewModel ()
  {
    //Override.
  }

  /**
   * Performs all processing related to the view generation.
   * @return ResponseInterface
   * @throws FileNotFoundException
   */
  private function processView ()
  {
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
    $output = $view->render ($this);
    $this->response->getBody ()->write ($output);
    return $this->response;
  }

}
