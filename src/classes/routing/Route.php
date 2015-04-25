<?php

class Route extends AbstractRoute
{
  public $onMenu = false;
  public $module;
  public $dataModule;
  public $preset;
  public $model;
  public $format;
  public $fields;
  public $config;

  public function __construct (array &$init = null)
  {
    parent::__construct ($init);
  }

  public function getTypes ()
  {
    return [
      'name'           => 'string',
      'URI'            => 'string',
      'URL'            => 'string',
      'module'         => 'string',
      'dataModule'     => 'string',
      'preset'         => 'string',
      'model'          => 'string',
      'format'         => 'string',
      'routes'         => 'array',
      'fields'         => 'string',
      'autoView'       => 'boolean',
      'autoController' => 'boolean'
    ];
  }

  public function getModel ()
  {
    global $model;
    $modelName = property ($this, 'model');
    if (!isset($modelName)) {
      if (isset($this->dataSources)) {
        $ds = get ($this->dataSources, 'default');
        if (isset($ds))
          $modelName = $ds->model;
      }
    }
    else $modelName = property ($this, 'model');
    if (!isset($modelName))
      return null;
    //throw new ConfigException("Default data source model not found");
    $thisModel = get ($model, $modelName);
    if (!isset($thisModel))
      throw new ConfigException("Model <b>$modelName</b> was not found");
    return $thisModel;
  }

}
