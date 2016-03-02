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
    $this->merge (Http::getRouteParameters ($request));
    switch ($request->getMethod ()) {
      case 'GET':
        $this->merge ($this->session->getOldInput ());
        break;
      case 'POST':
        $contentType = $request->getHeaderLine ('Content-Type');
        if ($contentType == 'application/x-www-form-urlencoded' ||
            str_beginsWith ($contentType, 'multipart/form-data')
        ) {
          $data = $request->getParsedBody ();
          unset ($data[Http::ACTION_FIELD]);
          $this->merge ($data);
        }
    }
    $this->runExtensions();
  }

  function loadRequested ($modelClass, $param = 'id')
  {
    // does nothing
  }

  function merge (array $data = null)
  {
    if (is_null ($data) || is_null ($this->model)) return;
    if (is_array ($this->model))
      array_mergeExisting ($this->model, array_normalizeEmptyValues ($data));
    else if (is_object ($this->model))
      extendExisting ($this->model, array_normalizeEmptyValues ($data));
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

  function saveModel ()
  {
    // does nothing
  }

  protected function runExtensions ()
  {
    foreach ($this->extensions as $ext) {
      $extension = is_string ($ext) ? $this->injector->make ($ext) : $ext;
      $extension->modelControllerExtension ($this);
    }
  }

  protected function callEventHandlers (array $handlers)
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

}
