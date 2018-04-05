<?php
namespace Electro\Database\Services;

use Electro\Database\Lib\FlexibleModel;

/**
 * A Model Controller for models which, besides having the usual fixed database fields, also have a variable number of
 * virtual fields that are stored on a single physical field as a JSON string.
 */
class FlexibleModelController extends ModelController
{
  /** @var FlexibleModel */
  protected $model;

  function loadModel ($modelClassOrCollection, $subModelPath = '', $id = null, $primaryKey = null)
  {
    if (!is_subclass_of ($modelClassOrCollection, FlexibleModel::class))
      throw new \InvalidArgumentException ("The '$modelClassOrCollection' collection class is not a valid subclass of " .
                                           FlexibleModel::class);
    /** @var FlexibleModel $instance */
    $instance   = is_string($modelClassOrCollection) ? new $modelClassOrCollection : $modelClassOrCollection;

    $id                = $id ?: $this->requestedId;
    $this->requestedId = $id;
    $this->primaryKey  = $primaryKey = $primaryKey ?: $this->primaryKey ?:  $instance::PRIMARY_KEY_FIELD;
    $this->collection  = $instance::TABLE ?: $modelClassOrCollection;

    $data = $this->pdo->query ("SELECT * FROM $this->collection WHERE \"$primaryKey\"=?", [$id])->fetch ();

    $jsonData = $data ? json_decode (get ($data, $instance::JSON_FIELD, '[]'), true) : [];
    extend ($instance, $jsonData);

    // Add the physical fields to the final model data, as virtual fields
    $physicalFields = array_unique (array_merge ($instance::PHYSICAL_FIELDS, [$primaryKey]));
    foreach ($physicalFields as $fld)
      $instance->$fld = get ($data, $fld);

    if ($subModelPath === '')
      $this->model = $data;
    else setAt ($this->model, $subModelPath, $data);
    return $data;
  }

  function saveModel ()
  {
    $instance = $this->model;

    // Split virtual and physical fields
    $physicalFields = array_flip ($instance::PHYSICAL_FIELDS) + [$this->primaryKey => 0];
    $physicalValues = [];
    $jsonValues     = [];
    foreach ($instance as $k => $v)
      if (isset($physicalFields[$k]))
        $physicalFields[$k] = $v;
      else $jsonValues[$k] = $v;

    $physicalValues[$instance::JSON_FIELD] = json_encode ($jsonValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $this->doSave ($physicalValues);
    return true;
  }

}
