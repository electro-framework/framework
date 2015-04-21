<?php
class FieldFormat {
  const LINE      = 'input type=line';
  const MULTILINE = 'input type=multiline';
  const PASSWORD  = 'input type=password';
  const DATE      = 'input type=date';
  const HTML      = 'html-editor';
  const CHECKBOX  = 'checkbox';
  const SELECTOR  = 'selector';
  const IMAGE     = 'image-field';
  const FILE      = 'file-upload';
  const VIDEO     = 'video-field';
  const HIDDEN    = 'hidden-field';
  const LITERAL   = 'literal';
  const BUTTON    = 'button';
}

class PageFormat {
  const FORM = 'form';
  const GRID = 'grid';
}

class FieldOptions {
  const READONLY              = 0x001;
  const HTML                  = 0x002;
  const VALUE_IS_URI          = 0x010;
  const VALUE_IS_SCRIPT       = 0x020;
  const VALUE_IS_ACTION       = 0x040;
  const DISABLE_ON_NEW_RECORD = 0x100;
}

class FieldInfo extends Object {
  public $id = null;
  public $name = null;
  public $label = '';
  public $format; //enum: line,multiline,password,date,select
  public $required = FALSE;
  public $style;
  public $options = 0;
  public $data = null;
  public $dataSource = null;
  public $valueField;
  public $labelField;
  public $value = '';
  public $text = '';
  public $param = '';

  public function getTypes() {
    return array(
      'id'         => 'string',
      'name'       => 'string',
      'label'      => 'string',
      'format'     => 'string',
      'required'   => 'boolean',
      'style'      => 'string',
      'options'    => 'integer',
      'data'       => 'array',
      'dataSource' => 'string',
      'valueField' => 'string',
      'labelField' => 'string',
      'value'      => 'string',
      'text'       => 'string',
      'param'      => 'string'
    );
  }

  public function __construct(array $init) {
    parent::__construct($init);
  }

}
