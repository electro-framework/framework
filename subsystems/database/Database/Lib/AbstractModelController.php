<?php
namespace Electro\Database\Lib;

use Electro\Exceptions\FatalException;
use Electro\Http\Lib\Http;
use Electro\Interfaces\ModelControllerExtensionInterface;
use Electro\Interfaces\ModelControllerInterface;
use Electro\Interfaces\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Platform\Config\PlatformModule;

abstract class AbstractModelController implements ModelControllerInterface
{
  /**
   * @var array|object
   */
  protected $model = null;
  /**
   * @var string A dot-separated path to the root/main model.
   * @see setModelRootPath
   */
  protected $modelRootPath = 'model';
  /**
   * @var array|null If null, all route parameters are used as presets.
   */
  protected $presets = null;
  /**
   * @var ServerRequestInterface
   */
  protected $request;
  /**
   * @var mixed
   */
  protected $requestedId = null;
  /**
   * @var string[]|callable[]|ModelControllerExtensionInterface[]
   */
  private $extensions = [];
  /**
   * A pipeline of operations to be performed after saving the model.
   * At this point, the transaction has already been commited, so no further database operations should be performed.
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
  private $handlersForSave;
  /**
   * @var callable|null
   */
  private $overrideDefaultHandler = null;
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (SessionInterface $session)
  {
    $this->session         = $session;
    $this->handlersForSave = [[$this, 'builtInSaveHandler']];
  }

  function get ($path)
  {
    $root = "$this->modelRootPath.";
    $p    = strlen ($root);
    return str_beginsWith ($path, $root)
      ? getAt ($this->model, substr ($path, $p))
      : ($path == $this->modelRootPath ? $this->model : null);
  }

  function getModel ($subModelPath = '')
  {
    if ($subModelPath === '')
      return $this->model;
    else return getAt ($this->model, $subModelPath);
  }

  function setModel ($data, $subModelPath = '')
  {
    if ($subModelPath === '')
      $this->model = $data;
    else setAt ($this->model, $subModelPath, $data);
  }

  function getRequest ()
  {
    return $this->request;
  }

  function setRequest (ServerRequestInterface $request)
  {
    $this->request = $request;
  }

  function getTarget ($path)
  {
    $z          = explode ('.', $path);
    $prop       = array_pop ($z);
    $targetPath = implode ('.', $z);
    return [$this->get ($targetPath), $prop];
  }

  function handleRequest ()
  {
    $request = $this->request;

    // Merge route parameters.
    $rp = Http::getRouteParameters ($request);
    if ($rp) {
      $o = [];
      $presets = isset($this->presets) ? $this->presets : array_keys ($rp);
      foreach ($presets as $k => $v) {
        if (is_integer ($k))
          $k = $v;
        $o[$v] = get ($rp, $k);
      }
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

          unset ($data[PlatformModule::ACTION_FIELD]);
          $this->merge ($data);
        }
    }
    $this->runExtensions ();
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

  function preset (array $presets)
  {
    $this->presets = $presets;
    return $this;
  }

  function registerExtension ($extension)
  {
    $this->extensions[] = $extension;
  }

  function saveModel ()
  {
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

  function set ($path, $value)
  {
    $root = "$this->modelRootPath.";
    $p    = strlen ($root);
    if (str_beginsWith ($path, $root))
      setAt ($this->model, substr ($path, $p), $value);
    return $this;
  }

  function setModelRootPath ($path)
  {
    $this->modelRootPath = $path;
  }

  /**
   * Saves all sub-models on the model that can be automatically saved.
   *
   * <p>Although not common, you may override this if the model has some unsupported format that can not be handled by
   * the default implementation. Arrays of models and objects with models on public properties are supported.
   *
   * @return bool
   */
  protected function saveCompositeModel ()
  {
    foreach ($this->model as $submodel)
      if (!$this->save ($submodel))
        return false;
    return true;
  }

  /**
   * The default save handler.
   * @return bool
   */
  private function builtInSaveHandler ()
  {
    $def = $this->overrideDefaultHandler;
    if ($def)
      return $def ($this);

    $model = $this->model;
    $s     = $this->save ($model);
    if (is_null ($s))
      $s = $this->saveCompositeModel ();
    return $s;
  }

  private function callEventHandlers (array $handlers)
  {
    array_walk ($handlers, function ($fn) {
      return $fn ($this);
    });
  }

  /**
   * Constructs a tree-like structure from flat form-submitted data.
   *
   * <p>Keys with slashes are expanded into nested arrays.
   * <p>Keys not starting with {@see modelRootPath} are excluded.
   *
   * @param array $data
   * @return array
   */
  private function parseFormData (array $data)
  {
    $o    = [];
    $root = "$this->modelRootPath.";
    $p    = strlen ($root);
    foreach ($data as $k => $v) {
      $k = str_replace ('/', '.', $k);
      if (str_beginsWith ($k, $root))
        setAt ($o, substr ($k, $p), $v, true);
    }
    return $o;
  }

  private function runExtensions ()
  {
    foreach ($this->extensions as $ext) {
      if (is_string ($ext))
        $extension = new $ext;
      elseif (is_callable ($ext))
        $extension = $ext ();
      else $extension = $ext;
      $extension->modelControllerExtension ($this);
    }
  }

}
