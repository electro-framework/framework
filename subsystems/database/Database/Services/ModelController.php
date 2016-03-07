<?php
namespace Selenia\Database\Services;

use PhpKit\ExtPDO;
use Selenia\Database\Lib\ABstractModelController;
use Selenia\Interfaces\SessionInterface;

class ModelController extends AbstractModelController
{
  /** @var ExtPDO */
  private $pdo;

  public function __construct (SessionInterface $session, ExtPDO $pdo)
  {
    parent::__construct ($session);
    $this->pdo = $pdo;
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

  function loadData ($collection, $subModelPath = '', $id = null, $primaryKey = 'id')
  {
    $id                = $this->requestedId ?: $id;
    $this->requestedId = $id;

    $data = $this->sql->query ("SELECT * FROM $collection WHERE $primaryKey=?", [$id])->fetch ();
    if ($subModelPath === '')
      $this->model = $data;
    else setAt ($this->model, $subModelPath, $data);
    return $data;
  }

  function loadModel ($modelClass, $subModelPath = '', $id = null)
  {
    // Does nothing; this implementation (obviously) does not support an ORM.
  }

  /**
   * Override to provide an implementation of a database transaction rollback.
   */
  protected function rollback ()
  {
    $this->pdo->rollBack ();
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
    // Does nothing; there's no automated saving support on this implementation yet.
    return false;
  }

}
