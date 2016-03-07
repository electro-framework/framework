<?php
namespace Selenia\Database\Services;

use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\FatalException;
use Selenia\Http\Lib\Http;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModelControllerExtensionInterface;
use Selenia\Interfaces\ModelControllerInterface;
use Selenia\Interfaces\SessionInterface;

class ModelController implements ModelControllerInterface
{
  /**
   * For composite models, this is a dot-separated path to the main model.
   *
   * <p>The main model is the target for automatic route parameters merging.
   * <p>If it's an empty string, the whole model is the target.
   *
   * @var string
   */
  public $mainModelName = '';
  /**
   * @var InjectorInterface
   */
  protected $injector;
  /**
   * @var array|object
   */
  protected $model = [];
  /**
   * @var ServerRequestInterface
   */
  protected $request;
  /**
   * @var array Driver/ORM-specific options for the default save handler.
   */
  private $defaultOptions;
  /**
   * @var string[]|ModelControllerExtensionInterface[]
   */
  private $extensions = [];
  /**
   * A pipeline of operations to be performed after saving the model.
   * At this point, the transaction has alread been commited, so no further database operations should be performed.
   * You can, though, do other kinds of cleanup operations, like deleting files, for instance.
   *
   * @var callable[]
   */
  private $handlersForPostSave = [];
  /**
   * A pipeline of operations to be performed on the model before saving it.
   * At this point, the transaction has not yet began, so no database operations should be performed yet.
   * You can, though, do other kinds of operations, like preparing uploaded files, for instance.
   *
   * @var callable[]
   */
  private $handlersForPreSave = [];
  /**
   * A pipeline of operations to save the model.
   * These are all performed within a database transaction.
   * You mayn prepend callbacks, to perform database operations before the model is saved, or you may append callbacks,
   * to perform operations after the model is saved. You may also replace the default model saver.
   *
   * @var callable[]
   */
  private $handlersForSave = [];
  /**
   * @var callable|null
   */
  private $overrideDefaultHandler = null;
  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct (InjectorInterface $injector, SessionInterface $session)
  {
    $this->session  = $session;
    $this->injector = $injector;
    $this->onSave (0, [$this, 'builtInSaveHandler']);
  }

  function getModel ()
  {
    return $this->model;
  }

  function setModel ($data)
  {
    $this->model = $data;
  }

  function getRequest ()
  {
    return $this->request;
  }

  function setRequest (ServerRequestInterface $request)
  {
    $this->request = $request;
  }

  function handleRequest ()
  {
    $request = $this->request;

    // Merge route parameters.
    $rp = Http::getRouteParameters ($request);
    if ($rp) {
      $o = [];
      setAt ($o, $this->mainModelName, $rp);
      $this->merge ($o);
    }

    switch ($request->getMethod ()) {

      case 'GET':
        $old = $this->session->getOldInput ();
        if ($old)
          $this->merge ($this->parseFormData ($old));
        break;

      case 'POST':
        $data = $request->getParsedBody ();
        if (isset($data)) {

          $contentType = $request->getHeaderLine ('Content-Type');
          if ($contentType == 'application/x-www-form-urlencoded' ||
              str_beginsWith ($contentType, 'multipart/form-data')
          )
            $data = $this->parseFormData ($data);

          unset ($data[Http::ACTION_FIELD]);
          $this->merge ($data);
        }
    }
    $this->runExtensions ();
  }

  function loadModel ($modelClass, $path = '', $id = null)
  {
    // does nothing
  }

  function loadRequested ($modelClass, $modelName = '', $param = 'id')
  {
    // does nothing
  }

  function merge (array $data = null)
  {
    if (is_null ($data) || is_null ($this->model)) return;
    $data = array_normalizeEmptyValues ($data, true);
    if (is_array ($this->model))
      array_recursiveMergeInto ($this->model, $data);
    else if (is_object ($this->model))
      extend ($this->model, $data);
    else throw new FatalException (sprintf ("Can't merge data into a model of type <kbd>%s</kbd>",
      gettype ($this->model)));
  }

  function onAfterSave (callable $task)
  {
    $this->handlersForPostSave[] = $task;
  }

  function onBeforeSave (callable $task)
  {
    $this->handlersForPreSave[] = $task;
  }

  function onSave ($priority, callable $task)
  {
    if (!$priority)
      $this->overrideDefaultHandler = $task;
    elseif ($priority > 0)
      array_unshift ($this->handlersForSave, $task);
    else $this->handlersForSave[] = $task;
  }

  function registerExtension ($extensionClass)
  {
    $this->extensions[] = $extensionClass;
  }

  function saveModel (array $options = [])
  {
    $this->defaultOptions = $options;
    $this->callEventHandlers ($this->handlersForPreSave);
    $this->beginTransaction ();
    try {
      $this->callEventHandlers ($this->handlersForSave);
      $this->commit ();
    }
    catch (\Exception $e) {
      $this->rollback ();
      throw $e;
    }
    $this->callEventHandlers ($this->handlersForPostSave);
  }

  /**
   * Override to provide an implementation of beginning a database transaction.
   */
  protected function beginTransaction ()
  {
    //override
  }

  /**
   * Override to provide an implementation of a database transaction commit.
   */
  protected function commit ()
  {
    //override
  }

  /**
   * Override to provide an implementation of a database transaction rollback.
   */
  protected function rollback ()
  {
    //override
  }

  /**
   * Attempts to save the given model on the database.
   *
   * <p>If the model type is unsupported by the specific controller implementation, the method will do nothing and
   * return `false`.
   * > <p>This is usually only overriden by controller subclasses that implement support for a specific ORM.
   *
   * @param mixed $model
   * @param array $options Driver/ORM-specific options.
   * @return bool true if the model was saved.
   */
  protected function save ($model, array $options = [])
  {
    return false;
  }

  /**
   * Saves all sub-models on the model that can be automatically saved.
   *
   * <p>Although not common, you may override this if the model has some unsupported format that can not be handled by
   * the default implementation. Arrays of models and objects with models on public properties are supported.
   *
   * @param array $options Driver/ORM-specific options.
   * @return bool
   */
  protected function saveCompositeModel (array $options = [])
  {
    foreach ($this->model as $submodel)
      if (!$this->save ($submodel, $options))
        return false;
    return true;
  }

  private function builtInSaveHandler ()
  {
    $def = $this->overrideDefaultHandler;
    if ($def)
      return $def ($this);

    $model = $this->model;
    if (!($s = $this->save ($model, $this->defaultOptions)))
      $s = $this->saveCompositeModel ($this->defaultOptions);
    return $s;
  }

  private function callEventHandlers (array $handlers)
  {
    array_walk ($handlers, [$this, 'exec']);
  }

  /**
   * Calls an event handler.
   *
   * @param callable $fn
   * @return mixed
   */
  private function exec ($fn)
  {
    return $fn ($this);
  }

  private function parseFormData (array $data)
  {
    $o = [];
    foreach ($data as $k => $v) {
      $k = str_replace ('/', '.', $k);
      setAt ($o, $k, $v, true);
    }
    return $o;
  }

  private function runExtensions ()
  {
    foreach ($this->extensions as $ext) {
      $extension = is_string ($ext) ? $this->injector->make ($ext) : $ext;
      $extension->modelControllerExtension ($this);
    }
  }

}
