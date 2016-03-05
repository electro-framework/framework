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
   * A pipeline of operations to be performed on the model after saving it.
   *
   * @var callable[]
   */
  protected $postSaveHandlers = [];
  /**
   * A pipeline of operations to be performed on the model before saving it.
   *
   * @var callable[]
   */
  protected $preSaveHandlers = [];
  /**
   * @var ServerRequestInterface
   */
  protected $request;
  /**
   * @var string[]|ModelControllerExtensionInterface[]
   */
  private $extensions = [];
  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct (InjectorInterface $injector, SessionInterface $session)
  {
    $this->session  = $session;
    $this->injector = $injector;
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

  function handleRequest (ServerRequestInterface $request)
  {
    $this->request = $request;

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

  function loadModel ($modelClass, $modelName = '', $id = null)
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
    $this->postSaveHandlers[] = $task;
  }

  function onBeforeSave (callable $task)
  {
    $this->preSaveHandlers[] = $task;
  }

  function registerExtension ($extensionClass)
  {
    $this->extensions[] = $extensionClass;
  }

  function saveModel (array $options = [])
  {
    // does nothing
  }

  protected function callEventHandlers (array $handlers)
  {
    array_walk ($handlers, [$this, 'exec']);
  }

  protected function parseFormData (array $data)
  {
    $o = [];
    foreach ($data as $k => $v) {
      $k = str_replace ('/', '.', $k);
      setAt ($o, $k, $v, true);
    }
    return $o;
  }

  protected function runExtensions ()
  {
    foreach ($this->extensions as $ext) {
      $extension = is_string ($ext) ? $this->injector->make ($ext) : $ext;
      $extension->modelControllerExtension ($this);
    }
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

}
