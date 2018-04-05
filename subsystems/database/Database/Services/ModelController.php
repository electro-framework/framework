<?php
namespace Electro\Database\Services;

use Electro\Database\Lib\AbstractModelController;
use Electro\Interfaces\SessionInterface;
use PhpKit\ExtPDO\ExtPDO;
use PhpKit\ExtPDO\Interfaces\ConnectionInterface;

/**
 * A Model Controller that implements a simple, low-level, database access using PDO.
 *
 * <p>Models are simple arrays.
 * <p>Nested (sub)models are only partially supported by this implementation; to save them, the caller must handle it
 * (him)herself.
 */
class ModelController extends AbstractModelController
{
  /**
   * @var string The collection or table name.
   * It may or may not be used by descendentant classes.
   */
  protected $collection;
  protected $model = [];
  /** @var ExtPDO */
  protected $pdo;
  /** @var string A field name. */
  protected $primaryKey = 'id';

  public function __construct (SessionInterface $session, ConnectionInterface $connection)
  {
    parent::__construct ($session);
    $driver = $connection->driver ();
    if ($driver && $driver != 'none')
      $this->pdo = $connection->getPdo ();
  }

  function loadModel ($modelClassOrCollection, $subModelPath = '', $id = null, $primaryKey = null)
  {
    $id                = $id ?: $this->requestedId;
    $this->requestedId = $id;
    $this->primaryKey  = $primaryKey = $primaryKey ?: $this->primaryKey;
    $this->collection  = $modelClassOrCollection;

    if (class_exists ($modelClassOrCollection))
      throw new \RuntimeException ("The current Model Controller only supports raw array models.");

    $data = $this->pdo->query ("SELECT * FROM $modelClassOrCollection WHERE $primaryKey=?", [$id])->fetch ();
    if ($subModelPath === '')
      $this->model = $data;
    else setAt ($this->model, $subModelPath, $data);
    return $data;
  }

  function saveModel ()
  {
    $this->doSave ($this->model);
    return true;
  }

  function withRequestedId ($routeParam = 'id', $primaryKey = 'id')
  {
    $this->requestedId = $this->request->getAttribute ("@$routeParam");
    $this->primaryKey  = $primaryKey;
    return $this;
  }

  /**
   * Override to provide an implementation of beginning a database transaction.
   */
  protected function beginTransaction ()
  {
    $this->pdo->beginTransaction ();
  }

  /**
   * Override to provide an implementation of a database transaction commit.
   */
  protected function commit ()
  {
    $this->pdo->commit ();
  }

  /**
   * Encapsulates the saving functionality common to this class and its subclasses.
   *
   * @param array $data
   */
  protected function doSave (array $data)
  {
    list ($columns, $values) = array_divide ($data);
    if (isset($model[$this->primaryKey])) {
      $exp      = implode (',', array_map (function ($col) { return "\"$col\"=?"; }, $columns));
      $values[] = $model[$this->primaryKey];
      $this->pdo->exec ("UPDATE $this->collection SET $exp WHERE $this->primaryKey=?", $values);
    }
    else {
      $markers = array_fill (0, count ($columns), '?');
      $this->pdo->exec ("INSERT INTO $this->collection ($columns) VALUES ($markers)", $values);
    }
  }

  /**
   * Override to provide an implementation of a database transaction rollback.
   */
  protected function rollback ()
  {
    $this->pdo->rollBack ();
  }

}
