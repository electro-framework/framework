<?php

class ModuleOptions extends Object
{
  public $path;

  function __construct ($path, array $options)
  {
    $this->path = $path;
    parent::__construct ($options);
  }

  /**
   * Returns an array with keys for each class property name and values that define the property data type.
   * @return array
   */
  public function getTypes ()
  {
    return [
      'templates' => 'boolean',
      'views'     => 'boolean',
      'public'    => 'string',
      'lang'      => 'boolean',
      'bower'     => 'boolean',
      'grunt'     => 'boolean',
      'less'      => 'string',
      'config'    => 'array',
    ];
  }

  /**
   * @param boolean $v Does the module contains a templates directory?
   */
  function set_templates ($v)
  {
    global $application;
    if ($v)
      $application->templateDirectories[] = "$this->path/$application->moduleTemplatesPath";
  }

  function set_views ($v)
  {

  }

  /**
   * @param string $v Published URI for the module's public folder.
   */
  function set_public ($v)
  {
    global $application;
    $application->mount ($v, "$this->path/$application->modulePublicPath");
  }

  /**
   * @param boolean $v Does the module contains a translations folder?
   */
  function set_lang ($v)
  {
    global $application;
    if ($v)
      $application->languageFolders[] = "$this->path/$application->moduleLangPath";
  }

  function set_bower ($v)
  {

  }

  function set_grunt ($v)
  {

  }

  /**
   * @param string $v Name of the module's main LESS file to be compiled.
   */
  function set_less ($v)
  {

  }

  /**
   * @param array $v Configuration default settings to be merged into the application config.
   *                 Settings already defined will not be changed.
   */
  function set_config ($v)
  {
    global $application;
    foreach ($v as $section => $cfg) {
      $appCfg                        = get ($application->config, $section, []);
      $appCfg                        = $appCfg + $cfg;
      $application->config[$section] = $appCfg;
      if ($section == 'main')
        foreach ($appCfg as $k => $v)
          $application->$k = $v;
    }

  }

}
