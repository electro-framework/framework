<?php
namespace Selenia\Http\Components;

use Exception;
use Illuminate\Database\Eloquent\Model;
use PDOStatement;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionObject;
use Selenia\Application;
use Selenia\DataObject;
use Selenia\Exceptions\Fatal\DataModelException;
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\Flash\FileException;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Lib\PipeHandler;
use Selenia\Routing\Services\Router;
use Selenia\Traits\PolymorphicInjectionTrait;
use Selenia\ViewEngine\Engines\MatisseEngine;

/**
 * The base class for components that are web pages.
 */
class PageComponent extends CompositeComponent implements RequestHandlerInterface
{
  use PolymorphicInjectionTrait;

  /**
   * @var Application
   */
  public $app;
  /**
   * The link that matches the current URL.
   *
   * @var NavigationLinkInterface
   */
  public $currentLink;
  /**
   * The controller's data sources (view model)
   * The 'default' data source corresponds to the **model**.
   *
   * @var array
   */
  public $dataSources = [];
  /**
   * It's only set when using Matisse.
   *
   * @var DocumentFragment
   */
  public $document;
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
  /** @var NavigationInterface */
  public $navigation;
  /**
   * @var int Current page number.
   */
  public $pageNumber = 1;
  /**
   * If set, defines the page title. It will generate a document `<title>` and it can be used on
   * breadcrumbs.
   *
   * <p>Use this, instead of `title` to manually set the page title.
   *
   * @var string
   */
  public $pageTitle = null;
  /**
   * @var ServerRequestInterface
   */
  public $request;
  /**
   * The loader which has loaded this controller.
   *
   * @var Router
   */
  public $router;
  /**
   * @var SessionInterface
   */
  public $session;
  /**
   * An HTML fragment to display a status message or an empty string if no status message exists.
   *
   * @var string
   */
  public $statusMessage = '';
  /**
   * The HTTP request's virtual URL.
   *
   * @var string
   */
  public $virtualUrl;
  /**
   * The current request URI without the page number parameters.
   * This property is useful for databing with the expression {!controller.URI_noPage}.
   *
   * @var string
   */
  protected $URI_noPage;
  /**
   * When true and `$indexPage` is not set, upon a POST the page will redirect to the parent navigation link.
   *
   * @var bool
   */
  protected $autoRedirectUp = false;
  /**
   * Specifies the URL of the index page, to where the browser should navigate upon the
   * successful insertion / update / deletion of records.
   * If not defined on a subclass then the request will redisplay the same page.
   *
   * @var string
   */
  protected $indexPage = null;
  /**
   * @var RedirectionInterface
   */
  protected $redirection;
  /**
   * If set to true, the view will be rendered on the POST request without a redirection taking place.
   *
   * @var bool
   */
  protected $renderOnAction = false;
  /**
   * @var ViewServiceInterface
   */
  protected $view;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * Values to be automatically merged into the view model.
   *
   * @var array
   */
  private $presets = [];

  function __construct (InjectorInterface $injector, ViewServiceInterface $view, Application $app,
                        RedirectionInterface $redirection, SessionInterface $session, NavigationInterface $navigation,
                        PipeHandler $pipeHandler)
  {
    parent::__construct ();

    $this->injector    = $injector;
    $this->app         = $app;
    $this->redirection = $redirection;
    $this->session     = $session;
    $this->navigation  = $navigation;
    $this->view        = $view;
    $pipeHandler->registerFallbackHandler ($this);

    // Inject extra dependencies into the subclasses' inject methods, if one or more exist.

    $this->polyInject ();
  }

  /**
   * Performs the main execution sequence.
   *
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   * @throws FatalException
   * @throws FileException
   * @throws FlashMessageException
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->app)
      throw new FatalException("Class <kbd class=type>" . get_class ($this) .
                               "</kbd>'s constructor forgot to call <kbd>parent::__construct()</kbd>");
    $this->request    = $request;
    $this->virtualUrl = $request->getAttribute ('virtualUri');
    $this->redirection->setRequest ($request);

    $this->currentLink = $this->navigation->request ($this->request)->currentLink ();
    if (!$this->indexPage && $this->autoRedirectUp && $this->currentLink && $parent = $this->currentLink->parent ())
      $this->indexPage = $parent->url ();

    // remove page number parameter
    $this->URI_noPage =
      preg_replace ('#&?' . $this->app->pageNumberParam . '=\d*#', '', $this->request->getUri ()->getPath ());
    $this->URI_noPage = preg_replace ('#\?$#', '', $this->URI_noPage);

    $this->initialize (); //custom setup

    $this->model = $this->model ();
    $model       =& $this->model;
    $this->mergeIntoModel ($model, $this->getParameters ());
    switch ($this->request->getMethod ()) {
      case 'GET':
        if ($model) $this->mergeIntoModel ($model, $this->presets);
        $this->mergeIntoModel ($model, $this->session->getOldInput ());
        break;
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'POST':
        if ($this->request->getHeaderLine ('Content-Type') == 'application/x-www-form-urlencoded') {
//          dump (array_normalizeEmptyValues ( $this->request->getParsedBody ()));
          /** @var Model $model */
//          dump(typeOf($model));
//          dump($model->attributesToArray());
//          exit;
          $this->mergeIntoModel ($model, $this->request->getParsedBody ());
//          dump ($model);
//          exit;
        }
      default:
        $res = $this->doFormAction ();
        if (!$res && !$this->renderOnAction)
          $res = $this->autoRedirect ();
        $this->finalize ($res);
        return $res;
    }
    $this->viewModel ();

    // Render the component.

    $out = $this->getRendering ();
    $response->getBody ()->write ($out);
    $this->finalize ($response);
    return $response;
  }

  /**
   * Responds to the standard 'delete' controller action.
   * The default procedure is to delete the object on the database.
   * Override to implement non-standard behaviour.
   *
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
   *
   * @param string $param     A JQuery selector for the element that should automatically receive focus after the page
   *                          reloads.
   */
  function action_refresh ($param = null)
  {
    $this->renderOnAction = true;
    if ($param)
      $this->document->context->addInlineScript ("$('$param').focus()");
  }

  /**
   * Responds to the standard 'submit' controller action.
   * The default procedure is to either call insert() or update().
   * Override to implement non-standard behaviour.
   *
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

  /**
   * Merges data into the view model.
   *
   * @param array $data
   */
  function preset (array $data)
  {
    $this->presets = $data;
  }

  protected function autoRedirect ()
  {
    if (isset($this->indexPage))
      return $this->redirection->to ($this->indexPage);

    return $this->redirection->refresh ();
  }

  /**
   * Defines the set of fields which will be fetched to a data object from a POST request.
   * All other values on the request will be ignored.
   *
   * @return array If NULL all the data object's fields fields will be fetched.
   */
  protected function defineDataFields ()
  {
    return null;
  }

  /**
   * Sets the `statusMessage` view property to a rendered HTML status message.
   * <p>Override to define a different template or rendering mechanism.
   *
   * @param int    $status
   * @param string $message
   */
  protected function displayStatus ($status, $message)
  {
    if (!is_null ($status)) {
      if ($this->document)
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
   *
   * @return ResponseInterface|null
   * @throws FlashMessageException
   * @throws FileException
   */
  protected function doFormAction ()
  {
//    if (count ($_POST) == 0 && count ($_FILES) == 0)
//      throw new FileException(FileException::FILE_TOO_BIG, ini_get ('upload_max_filesize'));
    $this->getActionAndParam ($action, $param);
    $class = new ReflectionObject ($this);
    try {
      $method = $class->getMethod ('action_' . $action);
    }
    catch (ReflectionException $e) {
      throw new FlashMessageException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
        FlashType::ERROR);
    }
    return $method->invoke ($this, $param);
  }

  /**
   * Override to do something after the response has been generated.
   *
   * @param ResponseInterface $response
   */
  protected function finalize (ResponseInterface $response)
  {
    // no op
  }

  /**
   * Utility method for retrieving the value of a form field submitted via a `application/x-www-form-urlencoded` POST
   * request.
   *
   * @param string $name
   * @param        mixed [optional] $def
   * @return mixed
   */
  protected function formField ($name, $def = null)
  {
    return get ($this->request->getParsedBody (), $name, $def);
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

  protected function getParameters ()
  {
    return mapAndFilter ($this->request->getAttributes (), function ($v, &$k) {
      if ($k && $k[0] == '@') {
        $k = substr ($k, 1);
        return $v;
      }
      return null;
    });
  }

  protected function getRowOffset ()
  {
    return ($this->pageNumber - 1) * $this->app->pageSize;
  }

  protected function getTitle ()
    // override to return a dynamic title for the current page
  {
    return coalesce (
      $this->pageTitle,
      ($link = $this->navigation->selectedLink ()) ? $link->title () : null
    );
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
   *
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
    else if (is_object ($model)) {
      extendExisting ($model, array_normalizeEmptyValues ($data));
    }
    else throw new FatalException (sprintf ("Can't merge data into a model of type <kbd>%s</kbd>", gettype ($model)));
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

  function setupView (ViewInterface $view)
  {
    $engine = $view->getEngine ();
    if ($engine instanceof MatisseEngine) {
      $this->document  = $view->getCompiled ();
      $context         = $this->document->context;
      $title           = $this->getTitle ();
      $this->pageTitle = exists ($title) ? str_replace ('@', $title, $this->app->title) : $this->app->appName;
      $flashMessage    = $this->session->getFlashMessage ();
      if ($flashMessage)
        $this->displayStatus ($flashMessage['type'], $flashMessage['message']);
      foreach ($this->app->assets as $url) {
        $p = strrpos ($url, '.');
        if (!$p) continue;
        $ext = substr ($url, $p + 1);
        switch ($ext) {
          case 'css':
            $context->addStylesheet ($url);
            break;
          case 'js':
            $context->addScript ($url);
            break;
        }
      }
      $context->addScript ("{$this->app->frameworkURI}/js/engine.js");
      $context->getPipeHandler ()->registerFallbackHandler ($this);
      $context->viewModel = $this;
    }
    //------------------
    // DOM panel
    //------------------
    if (DebugConsole::hasLogger ('DOM')) {
      $insp = $this->document->inspect (true);
      DebugConsole::logger ('DOM')->write ($insp);
    }
    //------------------
    // View Model panel
    //------------------
    if (DebugConsole::hasLogger ('vm')) {
      DebugConsole::logger ('vm')->withFilter (function ($k, $v, $o) {
        if ($k === 'app' || $k === 'navigation' || $k === 'session' || $k === 'request' ||
            $k === 'currentLink' || $k === 'page' || $v instanceof NavigationLinkInterface
        ) return '...';
        return true;
      }, $this);
    }
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
   * Responds to the standard 'submit' controller action when a primary key value is present on the request.
   * The default procedure is to create a new record on the database if the model is an ORM model.
   * Override to implement your own saving algorithm.
   *
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

}
