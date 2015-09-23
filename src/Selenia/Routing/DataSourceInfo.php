<?php
namespace Selenia\Routing;

use Selenia\Controller;
use Selenia\DataObject;
use Selenia\Exceptions\ConfigException;
use Selenia\Matisse\DataRecord;
use Selenia\Matisse\DataSet;
use Selenia\Object;

class DataSourceFormat
{
  const DATA_SET    = 'dataset';
  const DATA_RECORD = 'record';
}

class DataSourceInfo extends Object
{
  public $format; //enum DataSourceFormat
  public $model;
  public $preset;
  public $filter    = '';
  public $params    = null;
  public $key       = 'id';
  public $value     = null;
  public $limit;
  public $pageParam = 'p';
  public $pageSize;
  public $sortBy    = '';
  /**
   * SQL field list. If not an empty string, this SQL expression will be used
   * to select fields on queries to the database. Subselects can be used to
   * emulate joins.
   * @var string
   */
  public $fields = '';

  private $dataItem;

  public function __construct (array $init)
  {
    parent::__construct ($init);
  }

  public function getTypes ()
  {
    return [
      'format'    => 'string',
      'model'     => 'string',
      'filter'    => 'string',
      'params'    => 'array',
      'preset'    => 'string',
      'key'       => 'string',
      'value'     => 'string',
      'limit'     => 'integer',
      'pageParam' => 'string',
      'pageSize'  => 'integer',
      'sortBy'    => 'string',
      'fields'    => 'string'
    ];
  }

  public function createDataItem ()
  {
    if (!isset($this->model))
      throw new ConfigException("Property <b>model</b> is required on a DataSourceInfo instance.");
    return new $this->model;
  }

  public function getDataItem ()
  {
    if (!isset($this->dataItem))
      $this->dataItem = $this->createDataItem ();
    return $this->dataItem;
  }

  public function getData (Controller $controller, $dataSourceName)
  {
    /** @var DataObject $dataItem */
    $dataItem = $this->getDataItem ();
    if (isset($this->key))
      $dataItem->{$this->key} = $this->value;
    if (isset($this->preset)) {
      $presets = explode ('&', $this->preset);
      foreach ($presets as $preset) {
        $presetParts = explode ('=', $preset);
        if ($presetParts[1][0] == '{') {
          $field                       = substr ($presetParts[1], 1, strlen ($presetParts[1]) - 2);
          $dataItem->{$presetParts[0]} = get ($controller->URIParams, $field);
        }
        else $dataItem->{$presetParts[0]} = $presetParts[1];
      }
    }
    if ($this->format == DataSourceFormat::DATA_RECORD) {
      if ($dataSourceName == 'default')
        $controller->standardDataInit ($dataItem);
      $dataItem->read ();
      $controller->interceptViewDataRecord ($dataSourceName, $dataItem);
      return new DataRecord($dataItem);
    }
    if (isset($this->pageSize)) {
      $page            = get ($_REQUEST, $this->pageParam, 1);
      $start           = ($page - 1) * $this->pageSize;
      $count           = $dataItem->queryBy ($this->filter, 'COUNT(*)', null, $this->params)->fetchColumn (0);
      $data            =
        $dataItem->queryBy ($this->filter, $this->fields, $this->sortBy, $this->params, "LIMIT $start,$this->pageSize")
                 ->fetchAll ();
      $controller->max = ceil ($count / $this->pageSize);
    }
    else $data = $dataItem->queryBy ($this->filter, $this->fields, $this->sortBy, $this->params,
      isset($this->limit) ? "LIMIT $this->limit" : '');
    $controller->interceptViewDataSet ($dataSourceName, $data);
    return new DataSet($data);
  }

}
