<?php
class ModelInfo extends Object {
  public $class;
  public $module;
  public $fields;
  public $form;
  public $fk;
  public $pk;

  public function getTypes() {
    return array(
      'class'  => 'string',
      'module' => 'string',
      'fields' => 'array',
      'form'   => 'object',
      'fk'     => 'array',
      'pk'     => 'string'
    );
  }

  public function __construct(array $init) {
    parent::__construct($init);
    if (!isset($this->fields))
      throw new ConfigException("Missing fields declaration on model.");
  }

  public static function loadModule($moduleName) {
    global $application;
    $path = "$application->modulesPath/$moduleName/config/model.php";
    if (!fileExists($path))
      $path = "$application->defaultModulesPath/$moduleName/config/model.php";
    $code = file_get_contents($path,FILE_USE_INCLUDE_PATH);
    if ($code === false)
      throw new ConfigException("ModelInfo::loadConfigOf can't load <b>$moduleName</b>'s model.");
    $val = evalPHP($code);
    if ($val === false)
      throw new ConfigException("Error on <b>$moduleName</b>'s model definiton. Please check the PHP code.");
    return $val;
  }
}
