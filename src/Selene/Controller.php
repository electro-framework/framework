<?php
namespace Selene;

use EmptyIterator;
use Exception;
use Impactwave\WebConsole\ErrorHandler;
use PDO;
use PDOStatement;
use ReflectionException;
use ReflectionObject;
use Selene\Exceptions\BaseException;
use Selene\Exceptions\ConfigException;
use Selene\Exceptions\DataModelException;
use Selene\Exceptions\FatalException;
use Selene\Exceptions\FileException;
use Selene\Exceptions\FileNotFoundException;
use Selene\Exceptions\SessionException;
use Selene\Exceptions\Status;
use Selene\Exceptions\ValidationException;
use Selene\Matisse\Components\Page;
use Selene\Matisse\DataRecord;
use Selene\Matisse\DataSet;
use Selene\Matisse\DataSource;
use Selene\Matisse\Exceptions\DataBindingException;
use Selene\Matisse\MatisseEngine;
use Selene\Routing\PageRoute;

ob_start ();

class Controller
{
  const FIND_TRANS_KEY  = '#\$([A-Z][A-Z0-9_]*)#';
  const MSG_SUCCESS     = "A informação foi guardada.";
  const MSG_FAILED      = "Não foi possível efectuar a operação.";
  const MSG_UNSUPPORTED = "A operação não foi implementada.";
  const MSG_DELETED     = 'O registo foi apagado.';
  const MSG_OK          = "A operação foi efectuada.";
  /**
   * The i18n cached translation table.
   * @var array An array of arrays indexed by language code.
   */
  protected static $translation  = [];
  public           $TEMPLATE_EXT = '.html';
  /**
   * A templating engine instance.
   * @var MatisseEngine
   */
  public $engine;
  /**
   * Points to the root of the components tree.
   * @var Page
   */
  public $page;
  /**
   * A two letter code for currently active language. NULL if i18n is disabled.
   * @var string
   */
  public $lang = null;
  /**
   * The human readable name of the active language (ex. English).
   * @var string
   */
  public $langLabel = null;
  /**
   * The ISO language code of the active language (ex. en-US).
   * @var string
   */
  public $langISO = null;
  /**
   * The locale language code of the active language (ex. en_US).
   * @var string
   */
  public $locale = null;
  /**
   * Array of information about each enabled language.
   * Each entry is in the format: 'langCode' => array('value'=>,'ISO'=>,'label'=>,'locale'=>)
   * @var array
   */
  public $langInfo;
  /**
   * If $dataClass is defined, the instantiated instance is stored in this property.
   * @var DataObject
   */
  public $dataItem = null;
  /**
   * @var string A & separated list of key=value pairs to initialize the dataItem.
   */
  public $preset = null;
  /**
   * If $dataClass is defined, this property may hold a comma-separated list of field names used
   * on the default query.
   * @var string
   */
  public $dataFields = '';
  /**
   * If $dataClass is defined, this property may hold a WHERE expression for the default query.
   * @var string
   */
  public $dataFilter = '';
  /**
   * If $dataClass is defined, this property may hold a SORT BY expression for the default query.
   * @var string
   */
  public $dataSortBy = '';
  /**
   * If $dataClass is defined, this property may hold an array with information about the parameters
   * automatically supplied to the query.
   * Each array entry is a string with a constant value or a databinding expression in the format:
   * {!dataSourceName.dataFieldName}
   * @var array
   */
  public $dataQueryParams = null;
  /**
   * If no sitemap is used, this property controls the creation of a default
   * data source.
   * Possible values are:
   * <p>
   * '' - no default datasource;<br/>
   * 'form' - create a single record default datasource;<br/>
   * 'grid' - create a multi-record default datasource.<br/>
   * </p>
   * @var String
   */
  public $defaultPageFormat = '';
  /**
   * Associative array of all components on the page which have an explicit ID.
   * @var array of Component
   */
  public $id = [];
  /**
   * The current request URI.
   * This property is useful for databing with the expression {!controller.URI}.
   */
  public $URI;
  /**
   * The current request URI without the page number parameters.
   * This property is useful for databing with the expression {!controller.URI_noPage}.
   */
  public $URI_noPage;
  /**
   * Information about the page associated with this controller.
   * @var PageRoute
   */
  public $sitePage;
  /**
   * The loader which has loaded this controller.
   * @var ModuleLoader
   */
  public $moduleLoader;
  /**
   * @var string The virtual URI following the ? symbol on the current page's URL.
   */
  public $virtualURI;
  /**
   * The current module's folder full physical URI.
   * @var string
   */
  public $moduleURI;
  /**
   * @var string The parameters on the request URL or an empty string if none are present.
   */
  public $requestParameters;
  /**
   * A list of parameter names (inferred from the page definition on the sitemap)
   * and correponding values present on the current URI.
   * @var array
   */
  public $URIParams;
  /**
   * Set to true to handle the request in a way more adapted to XML web services.
   * @var Boolean
   */
  public $isWebService = false;
  /**
   * Indicate if advanced XML/HTML view processing is enabled.
   * Set to false if your controller generates the response via respond().
   * @var boolean
   */
  public $viewProcessing = true;
  /**
   * Stores the POST information that was being sent before the login form appeared.
   * @var string
   */
  public $prevPost   = '';
  public $max        = 1;
  public $pageNumber = 1;
  /**
   * @var Boolean True if the page is running on the production web server.
   */
  public $isProductionSite = true;
  /**
   * True if the login form should be displayed.
   * @var bool
   */
  public $showLogin = false;
  /**
   * If specified on a subclass, the controller will automatically instantiate and initialize
   * a corresponding instance on setupModel() and also setup a default data source named 'default'
   * on setupViewModel().
   * @see Controller::dataItem
   * @var string The name of the class to be instantiated.
   */
  protected $dataClass = null;
  /**
   * If specified on a subclass, the controller will automatically invoke the specified method on an
   * instance of $dataClass to retrieve the default data source.
   * @var string
   */
  protected $modelMethod = null;
  /**
   * A list of languages codes for the available languages, as configured on Application.
   * @var string
   */
  protected $languages;
  /**
   * Specifies the URL of the index page, to where the browser should naviagate upon the
   * successful insertion / updatal of records.
   * If not defined on a subclass then the request will redisplay the same page.
   * @var string
   */
  protected $indexPage   = null;
  protected $redirectURI = null;

  public static function modPathOf ($virtualURI = '', $params = null)
  {
    global $application;
    if ($virtualURI == '')
      return '';
    $append = (!empty($params) ? '?' . $params : '');
    if ($virtualURI[0] == '/')
      return "$virtualURI$append";
    else return "$application->URI/$virtualURI$append";
  }

  public static function translate ($lang, $text)
  {
    /** @var ModuleLoader $loader */
    global $application, $loader;
    if (!isset(self::$translation[$lang])) {
      $paths   = [];
      $folders = array_reverse ($application->languageFolders);
      foreach ($folders as $folder) {
        $path = "$folder/$lang.ini";
        $z    = @parse_ini_file ($path);
        if (empty($z))
          $paths[] = "<li>" . ErrorHandler::shortFileName ($path);
        else self::$translation[$lang] = array_merge (get (self::$translation, $lang, []), $z);
      }
      if (empty($z))
        throw new BaseException("Translation file for language <b>$lang</b> was not found.<p>Search paths:<ul>" .
                                implode ('', $paths) . "</ul>", Status::FATAL);
    }
    return preg_replace_callback (self::FIND_TRANS_KEY, function ($args) use ($lang) {
      $a = $args[1];
      return empty(self::$translation[$lang][$a]) ? '$' . $a
        : preg_replace ('#\r?\n#', '<br>', self::$translation[$lang][$a]);
    }, $text);
  }

  public static function pageNotFound ($virtualURI = '')
  {
    global $application;

    if (substr ($virtualURI, 0, strlen ($application->appPublicPath)) == $application->appPublicPath) {
      http_response_code (404);
      echo "<h1>Not Found</h1><p>The requested file <b><code>$virtualURI</code></b> is missing.</p>";
      exit;
    }

    if (!empty($application->URINotFoundURL)) {
      if (preg_match ('#^(\w\w)/#', $virtualURI, $match))
        $lang = $match[1];
      else $lang = $application->defaultLang;
      $URI = str_replace ('{lang}', $lang, $application->URINotFoundURL);
      header ('Location: ' . $URI . '&URL=' . $_SERVER['REQUEST_URI'], true, 303);
      exit();
    }
    else throw new FatalException($virtualURI ? "<b>$virtualURI</b> is not a valid URI." : 'Invalid URI.');
  }

  /**
   * Performs the main execution sequence.
   * Provides support for:
   * - the standard GET/POST/redirect cycle;
   * - exception handling;
   * - authentication.
   * Every page controller should call this method.
   * Request handling has 2 phases:
   * 1 - processRequest() - optional - performs actions requested by the client;
   * 2 - processView() - optional - generates the user interface and any relevant information to display on the client.
   */
  public final function execute ()
  {
    global $application, $session, $controller;
    $controller   = $this;
    $authenticate = false;
    try {
      $this->URI = $_SERVER['REQUEST_URI'];
      // remove page number parameter
      /*
      $this->URI_noPage = preg_replace ('#&?' . $application->pageNumberParam . '=\d*#', '', $this->URI);
      $this->URI_noPage = preg_replace ('#\?$#', '', $this->URI_noPage);
      */
      $this->setupController ();
      $this->initTemplateEngine ();
      $this->configPage ();
      $this->initSession ();
      $authenticate = false;
      if (isset($session) && $application->isSessionRequired) {
        $this->getActionAndParam ($action, $param);
        $authenticate = true;
        if ($action == 'login') {
          $prevPost = get ($_POST, '_prevPost');
          try {
            $this->login ();
            if ($prevPost)
              $_POST = unserialize (urldecode ($prevPost));
            else $_POST = [];
            $_REQUEST = array_merge ($_POST, $_GET);
            if (empty($_POST))
              $_SERVER['REQUEST_METHOD'] = 'GET';
            $authenticate = false; // user is now logged in; proceed as a normal request
          } catch (SessionException $e) {
            $this->setStatus (Status::WARNING, $e->getMessage ());
            // note: if $prevPost === false, it keeps that value instead of (erroneously) storing the login form data
            if ($action)
              $this->prevPost = isset($prevPost) ? $prevPost : urlencode (serialize ($_POST));
          }
        }
        else {
          $authenticate = !$session->validate ();
          if ($authenticate && $action)
            $this->prevPost = urlencode (serialize ($_POST));
        }
      }
      $this->showLogin = $authenticate;
      $this->configLanguage ();
      $this->initSEO ();
      $this->initialize (); //custom setup
      if (!$authenticate) {
        // Normal page request (it's not a login form).
        $this->setupModel (); //custom setup. Note: this may load a BOM into the output buffer.
        if ($this->wasPosted ()) {
          if (!$this->isWebService)
            $this->setRedirection (); //defaults to the same URI
          try {
            $this->processRequest ();
            //if not a web service, the processing stops here.
          } catch (ValidationException $e) {
            $this->cancelRedirection ();
            throw $e;
          }
        }
      }
      if ($this->wasPosted ()) {
        if ($authenticate)
          $this->processView ($authenticate);
        $this->wrapWebServiceResponse ();
        $this->finishPostRequest ();
      }
      else if (is_null ($this->redirectURI)) {
        if (!$this->viewProcessing || !$this->processView ($authenticate)) {
          $this->respond ();
          $this->wrapWebServiceResponse ();
        }
      }
      $this->finalize ();
    } catch (Exception $e) {
      if ($e instanceof BaseException) {
        if (isset($this->redirectURI) && $e->getStatus () != Status::FATAL) {
          $this->setStatusFromException ($e);
          $this->finishPostRequest ();
        }
        @ob_clean ();
      }
      if (!($e instanceof BaseException) || $e->getStatus () == Status::FATAL) {
        throw $e;
      }
      else {
        $this->setStatusFromException ($e);
        try {
          if (!$this->processView ($authenticate)) //retry the view, this time displaying the error message
          {
            @ob_clean ();
            echo "<pre>" . $e->getMessage () . "\n\n" . htmlentities ($e->getTraceAsString ()) . "</pre>";
          }
        } catch (Exception $e) {
          echo "<pre>" . $e->getMessage () . "\n\n" . htmlentities ($e->getTraceAsString ()) . "</pre>";
        }
      }
    }
  }

  public function beginXMLResponse ()
  {
    header ('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="utf-8"?>';
  }

  public function getRowOffset ()
  {
    global $application;
    return ($this->pageNumber - 1) * $application->pageSize;
  }

  /**
   * Perform application-specific transformation on data source data before it is
   * stored for use on the view.
   * @param string $dataSourceName
   * @param array  $data a sequential array of dictionary arrays
   */
  public function interceptViewDataSet ($dataSourceName, array &$data)
  {
    // override
  }

  /**
   * Perform application-specific transformation on data source data before it is
   * stored for use on the view.
   * @param string $dataSourceName
   * @param mixed  $data can be an array or a DataObject
   */
  public function interceptViewDataRecord ($dataSourceName, $data)
  {
    // override
  }

  /**
   * Allows access to the components tree generated by the parsing process.
   * Component specific initialization can be performed here before the
   * page is rendered.
   * Override to add extra initialization.
   */
  public function setupView ()
  {
    global $application;
    $this->page->title = str_replace ('@', $this->getTitle (), $application->title);
    $this->page->addScript ("$application->frameworkURI/js/engine.js");
    $this->page->defaultDataSource = get ($this->engine->context->dataSources, 'default');
    $this->displayStatus ();
    $this->setDataSource ('page', new DataRecord($this->page));
  }

  /**
   * Initializes a data object for a typical GET request.
   * It is initialized either from the database by primary key value, or
   * initialized from values sent with the request itsef.
   * @param DataObject $data
   */
  public function standardDataInit (DataObject $data)
  {
    if (isset($data)) {
      if (isset($this->URIParams))
        extend ($data, $this->URIParams);
      if ($data->isInstanceRequested ()) {
        $data->setPrimaryKeyValue ($data->getRequestedPrimaryKeyValue ());
        if (!$data->read ())
          $data->initFromQueryString ();
        return;
      }
      if (!$data->isNew ())
        $data->read ();
      $data->initFromQueryString ();
    }
  }

  /**
   * Respondes to the standard 'submit' controller action.
   * The default procedure is to either call insert() or update().
   * Override to implement non-standard behaviour.
   * @param DataObject $data
   * @param null       $param
   * @throws BaseException
   */
  public function action_submit (DataObject $data = null, $param = null)
  {
    if (!isset($data))
      throw new BaseException('Can\'t insert/update NULL DataObject.', Status::FATAL);
    if ($data->isNew ())
      $this->insertData ($data, $param);
    else $this->updateData ($data, $param);
  }

  /**
   * Respondes to the standard 'delete' controller action.
   * The default procedure is to delete the object on the database.
   * Override to implement non-standard behaviour.
   * @param DataObject $data
   * @param null       $param
   * @throws BaseException
   * @throws DataModelException
   * @throws Exception
   * @throws FatalException
   */
  public function action_delete (DataObject $data = null, $param = null)
  {
    if (!isset($data))
      throw new BaseException('Can\'t delete NULL DataObject.', Status::FATAL);
    if (!isset($data->id) && isset($param)) {
      $data->setPrimaryKeyValue ($param);
      $data->read ();
    }
    $data->delete ();
    $this->setStatus (Status::INFO, self::MSG_DELETED);
    if (!$this->autoRedirect ())
      throw new FatalException("No index page defined.");
  }

  public function action_logout ()
  {
    global $session, $application;
    $session->logout ();
    $this->setRedirection (null, $application->URI);
  }

  public final function wasPosted ()
  {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
  }

  public final function getPageURI ()
  {
    $uri = $_SERVER['REQUEST_URI'];
    $i   = strpos ($uri, '?');
    if (!$i) return $uri;
    else return substr ($uri, 0, $i);
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
  public function setDataSource ($name, DataSource $data, $isDefault = false, $overwrite = true)
  {
    $name      = empty($name) ? 'default' : $name;
    $isDefault = $isDefault || $name == 'default';
    $ctx       = $this->engine->context;
    if ($isDefault) {
      if (isset($ctx->dataSources['default']) && !$overwrite)
        throw new DataBindingException(null,
          "The default data source for the page has already been set.\n\nThe current default data source is:\n<pre>$name</pre>");
    }
    $ctx->dataSources[$name] = $data;
  }

  /**
   * Assigns the specified data to a new (or existing) data source with the
   * specified name.
   * @param string $name The data source name.
   * @param mixed  $data An array, object or <i>null</i>.
   */
  public function setViewModel ($name, $data)
  {
    $ctx = $this->engine->context;
    if (!isset($data))
      $ctx->dataSources[$name] = new EmptyIterator();
    else if ((is_array ($data) && isset($data[0])) || $data instanceof PDOStatement)
      $ctx->dataSources[$name] = new DataSet($data);
    else $ctx->dataSources[$name] = new DataRecord($data);
  }

  /*
    protected function createDataItem($className,$dataModuleName = '') {
      if (!class_exists($className)) {
        if (!$dataModuleName || !isset($this->moduleLoader))
          throw new FatalException("Undefined data class <b>$className</b>.");
        $moduleName = property($this->sitePage,'dataModule',$this->moduleLoader->moduleInfo->module);
        if (!$this->moduleLoader->searchAndLoadClass($className,$dataModuleName))
          throw new FatalException("Couldn't load data class <b>$className</b> on module <b>$dataModuleName</b>.");
      }
      return new $className();
    }
  */

  public function getDataRecord ($name = null)
    //rarely overriden
  {
    if (is_null ($name)) {
      $ds = property ($this->page, 'defaultDataSource');
      if (isset($ds)) {
        $it = $ds->getIterator ();
        if ($it->valid ())
          return $it->current ();
        return null;
      }
      else throw new DataBindingException(null, "The default data source for the page is not defined.");
    }
    $ctx = $this->engine->context;
    if (array_key_exists ($name, $ctx->dataSources)) {
      $it = $ctx->dataSources[$name]->getIterator ();
      if ($it->valid ()) return $it->current ();
      return null;
    }
    throw new DataBindingException(null, "Data source <b>$name</b> is not defined.");
  }


  public function markerHit ($name)
  {
    //Override
  }

  protected function finalize ()
  {
    //override
  }

  protected function afterPageRender ()
  {
    //override
  }

  protected function initSession ()
  {
    global $session, $application;
    if (!$application->globalSessions)
      session_name ($application->name);
    $name = session_name ();
    session_start ();
    if ($application->autoSession) {
      $session                 = get ($_SESSION, 'sessionInfo', new Session);
      $session->name           = $name;
      $_SESSION['sessionInfo'] = $session;
    }
  }

  /**
   * This method may be overridden to try/catch login errors.
   * @return void
   */
  protected function login ()
  {
    global $session, $application;
    $session->login ($application->defaultLang);
  }

  protected function setupController ()
  {
    date_default_timezone_set ('Europe/Lisbon');
  }

  /**
   * Performs all processing related to the view generation.
   * @param bool $authenticate Is this a login form?
   * @return bool False if the view rendering was interrupted..
   * @throws FatalException
   * @throws FileNotFoundException
   */
  protected function processView ($authenticate = false)
  {
    global $application;
    $this->setupBaseModel ();
    if (!$authenticate) {
      // Normal page rendering (not a login form).

      $this->setupViewModel (); //custom setup
      if ($this->defineView ())
        return false;
    }
    else {
      // Show login form.
      $path = $application->loginView;
      if (!$this->loadView ($path))
        throw new FileNotFoundException($path);
      $this->page->formAutocomplete = true;
    }
    $this->setupView ();
    $output = $this->renderView ();
    $this->postProcess ($output);
    $this->afterPageRender ();
    return true;
  }

  /**
   * Creates and configures an instance of the template engine.
   */
  protected function initTemplateEngine ()
  {
    global $application;
    $this->engine = new MatisseEngine();
    $this->engine->registerComponents (Application::$TAGS);
    $ctx                      = $this->engine->context;
    $ctx->condenseLiterals    = $application->condenseLiterals;
    $ctx->debugMode           = $application->debugMode;
    $ctx->templateDirectories = $application->templateDirectories;
  }

  /**
   * Initializes the current page and related info.
   * Usually not overriden,
   * @throws ConfigException
   * @throws FatalException
   * @global Application $application
   */
  protected function configPage ()
  {
    global $application;
    if (isset($application->routingMap)) {
      if (!isset($this->moduleLoader))
        throw new ConfigException("The module for the current URI is not working properly.<br>You should check the class code.");
      $this->sitePage          = $this->moduleLoader->sitePage;
      $this->URIParams         = $this->sitePage->getURIParams ();
      $this->virtualURI        = $this->moduleLoader->virtualURI;
      $this->requestParameters = preg_match ('/&(.*)/', $this->URI, $matches) ? $matches[1] : null;
    }
  }

  /**
   * Initializes Search Engine Optimization information for the current page.
   * @global Application $application
   */
  protected function initSEO ()
  {
    global $application;
    if (isset($application->routingMap)) {
      if (isset($this->sitePage->keywords))
        $this->page->keywords =
          isset($this->lang) ? get ($this->sitePage->keywords, $this->lang, '') : $this->sitePage->keywords;
      if (isset($this->sitePage->description))
        $this->page->description =
          isset($this->lang) ? get ($this->sitePage->description, $this->lang, '') : $this->sitePage->description;
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
    global $application;
    if (!empty($application->productionIP))
      $this->isProductionSite = $_SERVER['SERVER_ADDR'] == $application->productionIP;
  }

  protected function configLanguage ()
  {
    global $application, $session;
    if (empty($application->languages))
      return;
    $this->languages = $application->languages;
    $this->langInfo  = [];
    foreach ($this->languages as $langDat) {
      $langDat                     = explode (':', $langDat);
      $this->langInfo[$langDat[0]] = [
        'value'  => $langDat[0],
        'ISO'    => $langDat[1],
        'label'  => $langDat[2],
        'locale' => explode ('|', $langDat[3])
      ];
    }
    $this->lang = firstNonNull ($this->lang, property ($session, 'lang'), $application->defaultLang);
    if (isset($session)) {
      if ($session->lang != $this->lang)
        $session->setLang ($this->lang);
    }
    if (isset($this->lang)) {
      if (!array_key_exists ($this->lang, $this->langInfo)) {
        $this->lang = $application->defaultLang;
        if (isset($session))
          $session->setLang ($this->lang);
        $this->setStatus (Status::ERROR, 'An invalid language was specified.');
      }
      $info = get ($this->langInfo, $this->lang);
      if (!isset($info))
        throw new ConfigException("Language <b>$this->lang</b> is not configured for this application.");
      $locales         = $this->langInfo[$this->lang]['locale'];
      $this->locale    = $locales[0];
      $this->langISO   = $this->langInfo[$this->lang]['ISO'];
      $this->langLabel = $this->langInfo[$this->lang]['label'];
      setlocale (LC_ALL, $locales);
    }
  }

  /**
   * Sets up a page specific data model for use on the processRequest() phase and/or on the processView() phase.
   * Override to provide specific functionality.
   */
  protected function setupModel ()
  {
    if (isset($this->sitePage)) {
      $thisModel = $this->sitePage->getModel ();

      if (!empty($thisModel)) {
        list ($this->dataClass, $this->modelMethod) = $this->evalModelRef ($thisModel);
        $this->dataItem = newInstanceOf ($this->dataClass);
        if (!isset($this->dataItem))
          throw new ConfigException("<p><b>Model class not found.</b>
  <li>Class:         <b>$this->dataClass</b>
  <li>Active module: <b>{$this->sitePage->module}</b>
");

        //if (isset($thisModel) && isset($thisModel->pk))
        //$this->dataItem->primaryKeyName = $thisModel->pk;
        $this->applyPresets ();
        $this->standardDataInit ($this->dataItem);
        return;
      }
    }
    if (isset($this->dataClass)) {
      $this->dataItem = newInstanceOf ($this->dataClass);
      //$this->dataItem = $this->createDataItem($this->dataClass);
      $this->applyPresets ();
      $this->standardDataInit ($this->dataItem);
    }
  }

  /**
   * Sets up page specific data sources for use on the processView() phase only.
   *
   * Models for use on the processRequest() phase should be defined on setupModel().
   * Override to provide specific functionality.
   * If <code>dataItem</code> is set, the default action is to create a default
   * data source with either a single record (if the primary key has a value)
   * or with a default list (if the primary key has no value).
   */
  protected function setupViewModel ()
  {
    global $application;
    $ctx              = $this->engine->context;
    $this->pageNumber = get ($_REQUEST, $application->pageNumberParam, 1);
    if (isset($this->sitePage)) {
      if (isset($this->dataItem)) {
        if ($this->sitePage->format == 'grid' && $this->dataItem->isNew ()) {
          if ($this->modelMethod)
            $st = $this->dataItem->{$this->modelMethod}($this);
          else $st =
            $this->dataItem->queryBy ($this->sitePage->filter, $this->sitePage->fieldNames, $this->sitePage->sortBy);
          $data = $st->fetchAll (PDO::FETCH_ASSOC);
          $this->paginate ($data);
          $this->interceptViewDataSet ('default', $data);
          $this->setDataSource ('', new DataSet($data), true);
        }
        else if ($this->sitePage->format == 'form') {
          $this->interceptViewDataRecord ('default', $this->dataItem);
          $this->setDataSource ('', new DataRecord($this->dataItem), true);
        }
      }
      //Initialize data sources defined on the sitemap
      if (isset($this->sitePage->dataSources))
        foreach ($this->sitePage->dataSources as $name => $dataSourceInfo)
          $this->setDataSource ($name, $dataSourceInfo->getData ($this, $name)); //interception is done inside getData()
    }
    else if (isset($this->dataItem))
      switch ($this->defaultPageFormat) {
        case 'form':
          $this->interceptViewDataRecord ('default', $this->dataItem);
          $this->setDataSource ('', new DataRecord($this->dataItem));
          break;
        case 'grid':
          if (isset($this->dataQueryParams)) {
            $params = [];
            foreach ($this->dataQueryParams as $param) {
              if ($param[0] == '{') {
                $tmp = explode ('.', substr ($param, 1, -1));
                if (count ($tmp)) {
                  $dataSource = substr ($tmp[0], 1);
                  $dataField  = $tmp[1];
                }
                else {
                  $dataSource = 'default';
                  $dataField  = $tmp[0];
                }
                $ds       = get ($ctx->dataSources, $dataSource);
                $it       = $ds->getIterator ()->current ();
                $params[] = isset($ds) ? get ($it, $dataField) : null;
              }
              else $params[] = $param;
            }
          }
          else $params = null;
          if ($this->modelMethod)
            $st = $this->dataItem->{$this->modelMethod}($this);
          else $st = $this->dataItem->queryBy ($this->dataFilter, $this->dataFields, $this->dataSortBy, $params);
          $data = $st->fetchAll (PDO::FETCH_ASSOC);
          $this->interceptViewDataSet ('default', $data);
          $this->paginate ($data);
          $this->setDataSource ('', new DataSet($data));
          break;
      }
  }

  protected function paginate (array &$data, $pageSize = 0)
  {
    global $application;
    if (!$pageSize)
      $pageSize = $application->pageSize;
    $this->pageNumber = get ($_REQUEST, $application->pageNumberParam, 1);
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

  protected function evalModelRef ($ref)
  {
    if (!empty($ref)) {
      $s = explode ('::', $ref);
      if (count ($s) == 2)
        return $s;
      return [$s[0], null];
    }
    return [null, null];
  }

  /**
   * Generates a response to a GET request when viewProcessing = false.
   */
  protected function respond ()
  {
    //override if required
  }

  /**
   * Loads or generates the view's source markup.
   * <p>Override to manually include the view's source markup.
   * @return bool Usually you should return false. Return <b>true</b> to cancel additional processing beyond this point.
   * @throws FatalException
   * @global Application $application
   */
  protected function defineView ()
  {
    global $application;

    ob_start ();
    $this->render ();
    $view = ob_get_clean ();
    if (strlen ($view)) {
      $this->parseView ($view);
      return false;
    }

    if (isset($this->moduleLoader)) {
      /** @var Module $info */
      $info     = $this->moduleLoader->moduleInfo;
      $viewFile = $this->sitePage->view;
      $path     = "$application->viewPath/$viewFile";
      $found    = $this->loadView ($path);
      if (!$found) {
        $path2 = "$info->path/$application->moduleViewsPath/$viewFile";
        $found = $this->loadView ($path2);
        if (!$found) {
          $path2 = ErrorHandler::shortFileName ($path2);
          throw new FatalException("View <b>$viewFile</b> was not found.<p>Search paths:<ul><li>$path<li>$path2</ul>");
        }
      }
      return false;
    }
    else {
      preg_match ('#(\w+?)\.php#', $this->URI, $match);
      if (!count ($match))
        throw new FatalException("Invalid URI <b>$this->URI</b>");
      $path = $application->viewPath . '/' . $match[1] . $this->TEMPLATE_EXT;
      return !$this->loadView ($path);
    }
  }

  /**
   * Allows subclasses to generate the view's markup dinamically.
   * If noting is sent to the output buffer from this method, the controller will try to load the view from metadata.
   */
  protected function render ()
  {
    // Override
  }

  protected function parseView ($viewTemplate)
  {
    $this->page             = $this->engine->parse ($viewTemplate);
    $this->page->controller = $this;
  }

  /**
   * Attempts to load the specified view file.
   * @param string $path
   * @return bool <b>true</b> if the file was found.
   * @throws FatalException
   * @throws FileNotFoundException
   */
  protected function loadView ($path)
  {
    $view = loadFile ($path);
    if (!$view)
      return false;
    $this->parseView ($view);
    return true;
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

  /**
   * Sets up a set of standard data sources which are available for databinding on all the application's views.
   * When overriden the parent class method should always be called.
   */
  protected function setupBaseModel ()
  {
    global $application, $session;
    $_SESSION['isValid'] = isset($session) && $session->isValid;
    $this->setDataSource ('application', new DataRecord($application));
    $this->setDataSource ('session', new DataRecord($_SESSION));
    $this->setDataSource ('sessionInfo', new DataRecord($session));
    $this->setDataSource ('controller', new DataRecord($this));
    $this->setDataSource ('request', new DataRecord($_REQUEST));
    if (isset($this->sitePage)) {
      $this->setDataSource ('sitePage', new DataRecord($this->sitePage));
      $this->setDataSource ('config', new DataRecord($this->sitePage->config));
    }
    if (isset($this->moduleLoader))
      $this->setDataSource ('module', new DataRecord($this->moduleLoader->moduleInfo));
    $this->setDataSource ('languages', new DataSet(isset($this->langInfo) ? array_values ($this->langInfo) : null));
    $this->setDataSource ("URIParams", new DataRecord($this->URIParams));
  }

  /**
   * Renders the components tree.
   */
  protected final function renderView ()
  {
    return $this->engine->render ($this->page);
  }

  /**
   * Performs post-rendering processing of the output stream, before it is sent to the client.
   */
  protected function postProcess ($content)
  {
    global $application;
    if ($application->translation && isset($this->lang))
      $content = self::translate ($this->lang, $content);
    $content = $this->postProcessHook ($content);
    if ($application->compressOutput && substr_count (get ($_SERVER, 'HTTP_ACCEPT_ENCODING', ''), 'gzip')) {
      header ("Content-Encoding: gzip");
      echo gzencode ($content, 1, FORCE_GZIP);
    }
    else {
      echo $content;
    }
  }

  /**
   * Allows page controllers to perform post processing on the generated HTML output
   * before it is sent to the browser.
   * @param String $content The page content that will be output to the browser.
   * @return String A replacement content.
   */
  protected function postProcessHook ($content)
  {
    return $content;
  }

  /**
   * Implements page specific action processing, in response to a POST request.
   * To implement standard behavior, override and make a call to $this->processForm($data),
   * where $data is the data object to be processed.
   * If you use the standard dataItem property, there is no need to override this method.
   */
  protected function processRequest ()
  {
    if (isset($this->dataItem))
      $this->processForm ($this->dataItem);
    else $this->processForm ();
  }

  /**
   * Responds to a POST request.
   * @param DataObject $data
   */
  protected final function processForm (DataObject $data = null)
  {
    if (isset($data)) {
      $data->loadFromHttpRequest ($this->defineDataFields ());
      $this->interceptFormData ($data);
    }
    $this->doFormAction ($data);
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

  protected function getActionAndParam (&$action, &$param)
  {
    $action = get ($_POST, '_action', '');
    if (preg_match ('#(\w*):(.*)#', $action, $match)) {
      $action = $match[1];
      $param  = $match[2];
    }
    else $param = null;
  }

  /**
   * Invokes the right controller method in response to the POST request's specified action.
   * @param DataObject $data
   * @throws BaseException
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
      throw new BaseException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
        Status::ERROR);
    }
    $method->invoke ($this, $data, $param);
  }

  protected function saveData ($data = null)
  {
    if (!isset($data))
      $data = $this->dataItem;
    if (isset($data) && $data->isModified ())
      $this->action_submit ($data);
  }

  /**
   * Respondes to the standard 'submit' controller action when a primary key value is not present on the request.
   * The default procedure is to create a new record on the database.
   * Override to implement non-standard behaviour.
   * @param DataObject $data
   * @param null       $param
   * @throws Exception
   */
  protected function insertData (DataObject $data, $param = null)
  {
    $data->insert ();
    $this->setStatus (Status::INFO, self::MSG_SUCCESS);
    if ($this->isWebService)
      echo "<pk>{$data->getPrimaryKeyValue()}</pk>";
    if (!$this->autoRedirect ())
      $this->setRedirection ($data->primaryKeyName . '=' .
                             DataObject::getNewPrimaryKeyValue ()); //only for standalone (non module) pages
  }

  /**
   * Respondes to the standard 'submit' controller action when a primary key value is present on the request.
   * The default procedure is to save the object to the database.
   * Override to implement non-standard behaviour.
   * @param DataObject $data
   * @param null       $param
   * @throws Exception
   */
  protected function updateData (DataObject $data, $param = null)
  {
    $data->update ();
    $this->setStatus (Status::INFO, self::MSG_SUCCESS);
    $this->autoRedirect ();
  }

  protected function getTitle ()
    // override to return the title of the current page
  {
    return isset($this->sitePage) ? $this->sitePage->getTitle () : '';
  }

  protected function finishPostRequest ()
    // override to implement actions to be performed before a redirection takes place
  {
    if (isset($this->redirectURI))
      $this->redirect ($this->redirectURI);
  }

  protected final function redirect ($url)
  {
    header ('Location: ' . $url, true, 303);
    exit();
  }

  protected final function cancelRedirection ()
  {
    $this->redirectURI = null;
  }

  protected final function setStatus ($status, $msg)
  {
    $_SESSION['formStatus']  = $status;
    $_SESSION['formMessage'] = $msg;
  }

  protected function clearStatus ()
  {
    unset($_SESSION['formStatus']);
  }

  protected final function setStatusFromException (BaseException $e)
  {
    $_SESSION['formStatus'] = $e->getStatus ();
    if ($e->getStatus () != Status::FATAL)
      $_SESSION['formMessage'] = $e->getMessage ();
    else {
      $msg = "{$e->getMessage()}\n\nOn {$e->getFile()}, line {$e->getLine()}\nStack trace:\n";
      $msg .= preg_replace ('/#\d/', '<li>', $e->getTraceAsString ());
      /*
            foreach($e->getTrace() as $trace)
            {
                    $msg.='<li>'.$trace['function'].'()';//.implode(',',$trace['args']).')';
                    $msg.="<br>at {$trace['file']}, line {$trace['line']}.</li>";
            }*/
      $_SESSION['formMessage'] = $msg;
    }
  }

  protected function displayStatus ()
  {
    $status = array_key_exists ('formStatus', $_SESSION) ? $_SESSION['formStatus'] : null;
    if (!is_null ($status)) {
      $this->clearStatus ();
      $message = array_key_exists ('formMessage', $_SESSION) ? $_SESSION['formMessage'] : null;
      if ($this->page)
        switch ($status) {
          case Status::FATAL:
            $this->page->fatal ($message);
            break;
          case Status::ERROR:
            $this->page->error ($message);
            break;
          case Status::WARNING:
            $this->page->warning ($message);
            break;
          default:
            $this->page->info ($message);
        }
      else echo $message;
    }
  }

  protected final function setRedirection ($redirectArgs = null, $redirectURI = null)
  {
    if (isset($redirectURI)) {
      if (isset($redirectArgs))
        $this->redirectURI = $redirectURI . '?' . $redirectArgs;
      else $this->redirectURI = $redirectURI;
    }
    else if (isset($redirectArgs))
      $this->redirectURI = $this->getPageURI () . '?' . $redirectArgs;
    else $this->redirectURI = $_SERVER['REQUEST_URI'];
  }

  protected final function thenGoTo ($virtualURI, $redirectArgs = null)
  {
    $this->redirectURI = $this->modPathOf ($virtualURI, $redirectArgs);
  }

  protected final function thenGoToSelf ($redirectArgs = null)
  {
    //$x = explode('?',$this->URI);
    //$args = count($x) > 1 ? $x[1] : '';
    //$this->redirectURI = $x[0].'?'.$args.(isset($redirectArgs) ? "&$redirectArgs" : '');
  }

  protected function gotoModuleIndex ()
  {
    if (isset($this->sitePage->indexURL))
      $this->thenGoTo ($this->sitePage->indexURL);
    else {
      /** @var PageRoute $index */
      $index = $this->sitePage->getIndex ();
      if (!$index)
        throw new ConfigException ("No index page found for URI " . $this->sitePage->URI);
      $this->thenGoTo ($index->evalURI ($this->URIParams));
    }
  }

  protected function autoRedirect ()
  {
    if ($this->isWebService)
      return true;
    if (isset($this->sitePage))
      $this->gotoModuleIndex ();
    else if (isset($this->indexPage))
      $this->setRedirection (null, $this->indexPage);
    else return false;
    return true;
  }

  protected function join ($masterSourceName, $slavesBaseName, $masterData, DataObject $slaveTemplate, $joinExpr,
                           $masterKeyField = 'id')
  {
    $ctx = $this->engine->context;
    if (!isset($ctx->dataSources[$masterSourceName]))
      $this->setDataSource ($masterSourceName, new DataSet($masterData));
    foreach ($masterData as &$record) {
      $slaveData    = clone $slaveTemplate;
      $slaveDataSet = new DataSet($slaveData->queryBy ($joinExpr, null, null, [$record[$masterKeyField]]));
      $this->setDataSource ($slavesBaseName . $record[$masterKeyField], $slaveDataSet);
    }
  }

  /**
   * Installs the module on the application.
   * Performs module initialization operations, including the creation of tables
   * on the database if they are not defined yet.
   * This method is called only when the user manually requests an application
   * configuration re-check.
   */
  protected function setupModule ()
  {
    //Override
  }

  /**
   * Called when a Marker component is parsed. This gives the controller the
   * opportunity to replace the marker with some other content.
   * @return array Array or Object: one or more components.
   */
  private function wrapWebServiceResponse ()
  {
    if ($this->isWebService) {
      $output = trim (ob_get_clean ());
      if (strlen ($output)) {
        $this->beginXMLResponse ();
        echo $output;
      }
    }
  }

  private function applyPresets ()
  {
    if (isset($this->preset)) {
      $presets = explode ('&', $this->preset);
      foreach ($presets as $preset) {
        $presetParts = explode ('=', $preset);
        if ($presetParts[1][0] == '{') {
          $field                             = substr ($presetParts[1], 1, strlen ($presetParts[1]) - 2);
          $this->dataItem->{$presetParts[0]} = get ($this->URIParams, $field);
        }
        else $this->dataItem->{$presetParts[0]} = $presetParts[1];
      }
    }
  }

}