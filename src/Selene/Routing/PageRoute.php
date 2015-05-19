<?php
namespace Selene\Routing;

class PageRoute extends AbstractRoute
{
  public $dataModule;
  public $preset;
  public $pagesMenu  = false;
  public $singular;
  public $plural;
  public $gender;
  public $format; //[form,list]
  public $config;
  public $model;
  public $links;
  public $isIndex    = false;
  public $dataSources;
  public $keywords;
  public $description;
  public $filter     = '';
  public $fieldNames = '';
  public $sortBy     = '';
  public $view       = '';

  public function __construct (array &$init = null)
  {
    parent::__construct ($init);
  }

  public function getTypes ()
  {
    return [
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
      'autoloadModel'  => 'boolean',
      'routes'         => 'array',
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
      'view'           => 'string',
      'controller'     => 'string',
      'autoController' => 'boolean',
      'icon'           => 'string',
      'keywords'       => '',
      'description'    => '',
      'fieldNames'     => 'string',
      'filter'         => 'string',
      'sortBy'         => 'string'
    ];
  }

  public function getModel ()
  {
    $modelName = property ($this, 'model');
    if (!isset($modelName)) {
      if (isset($this->dataSources)) {
        $ds = get ($this->dataSources, 'default');
        if (isset($ds))
          $modelName = $ds->model;
      }
    }
    else $modelName = $this->model;
    if (!isset($modelName))
      return null;
    //throw new ConfigException("Default data source model not found");
    return $modelName;
  }

  public function getDataModule ()
  {
    return isset($this->dataModule) ? $this->dataModule : $this->module;
  }

  protected function getDefaultSubtitle ()
  {
    switch ($this->format) {
      /*
      case PageFormat::FORM:
        if (isset($this->singular))
          return ucfirst($this->singular);*/
      case 'grid':
        if (isset($this->plural))
          return ucfirst ($this->plural);
    }
    return $this->parent->getDefaultSubtitle ();
  }

}
