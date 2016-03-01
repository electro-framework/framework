<?php
namespace Selenia\Database\Services;

use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\FatalException;
use Selenia\Http\Lib\Http;
use Selenia\Interfaces\ModelControllerInterface;
use Selenia\Interfaces\SessionInterface;

class ModelController implements ModelControllerInterface
{
  /**
   * @var array|object
   */
  protected $model = [];
  /**
   * A pipeline of operations to be performed on the model after saving it.
   *
   * @var callable[]
   */
  protected $postSavePipeline = [];
  /**
   * A pipeline of operations to be performed on the model before saving it.
   *
   * @var callable[]
   */
  protected $preSavePipeline = [];
  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct (SessionInterface $session)
  {
    $this->session = $session;
  }

  function getModel ()
  {
    return $this->model;
  }

  function setModel ($data)
  {
    $this->model = $data;
  }

  function handleRequest (ServerRequestInterface $request)
  {
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
  }

  /**
   * @param array|null $data If `null` nothihg happens.
   * @throws FatalException
   */
  function merge (array $data = null)
  {
    if (is_null ($data) || is_null ($this->model)) return;
    if (is_array ($this->model))
      array_mergeExisting ($model, array_normalizeEmptyValues ($data));
    else if (is_object ($this->model))
      extendExisting ($this->model, array_normalizeEmptyValues ($data));
    else throw new FatalException (sprintf ("Can't merge data into a model of type <kbd>%s</kbd>",
      gettype ($this->model)));
  }

  function onAfterSave (callable $task)
  {
    $this->postSavePipeline[] = $task;
  }

  function onBeforeSave (callable $task)
  {
    $this->preSavePipeline[] = $task;
  }

  function saveModel ()
  {
    // does nothing
  }

  protected function onGetModel ($model)
  {

  }

  protected function onPostModel ($model)
  {

  }

  protected function runPipeline (array $pipeline)
  {
    array_walk ($pipeline, [$this, 'exec']);
  }

  private function exec (callable $fn)
  {
    return $fn ($this);
  }

  /**
   * @param callable $plugin
   * @return mixed
   */
  function registerPlugin (callable $plugin)
  {
    // TODO: Implement registerPlugin() method.
  }
}
