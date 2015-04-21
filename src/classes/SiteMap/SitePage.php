<?php

class SitePage extends SiteElement
{
  public $module;
  public $dataModule;
  public $preset;
  public $pagesMenu = false;
  public $singular;
  public $plural;
  public $gender;
  public $format; //[form,list]
  public $config;
  public $model;
  public $links;
  public $isIndex = false;
  public $dataSources;
  public $keywords;
  public $description;
  public $filter = '';
  public $fieldNames = '';
  public $sortBy = '';

  public function getTypes ()
  {
    return array(
      'name'           => 'string',
      'title'          => 'string',
      'subtitle'       => 'string',
      'URI'            => 'string',
      'URIAlias'       => 'string',
      'URL'            => 'string',
      'subnavURI'      => 'string',
      'subnavURL'      => 'string',
      'onMenu'         => 'boolean',
      'select'         => 'string',
      'module'         => 'string',
      'dataModule'     => 'string',
      'preset'         => 'string',
      'pages'          => 'array',
      'pagesMenu'      => 'boolean',
      'singular'       => 'string',
      'plural'         => 'string',
      'gender'         => 'string',
      'format'         => 'string',
      'model'          => 'string',
      'config'         => 'array',
      'links'          => 'array',
      'isIndex'        => 'boolean',
      'indexURL'       => 'string',
      'dataSources'    => 'array',
      'autoView'       => 'boolean',
      'autoController' => 'boolean',
      'icon'           => 'string',
      'keywords'       => '',
      'description'    => '',
      'fieldNames'     => 'string',
      'filter'         => 'string',
      'sortBy'         => 'string'
    );
  }

  public function __construct (array &$init = null)
  {
    parent::__construct ($init);
  }

  protected function getDefaultSubtitle ()
  {
    switch ($this->format) {
      /*
      case PageFormat::FORM:
        if (isset($this->singular))
          return ucfirst($this->singular);*/
      case PageFormat::GRID:
        if (isset($this->plural))
          return ucfirst ($this->plural);
    }
    return $this->parent->getDefaultSubtitle ();
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
    } else $modelName = property ($this, 'model');
    if (!isset($modelName))
      return null;
    //throw new ConfigException("Default data source model not found");
    return $modelName;
  }

  public function getDataModule ()
  {
    return isset($this->dataModule) ? $this->dataModule : $this->module;
  }

}
