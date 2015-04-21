<?php
class FormActions {
  const SAVE = 'save';
  const SAVE_DELETE = 'save-delete';
  const SAVE_DELETE_PREVIEW = 'save-delete-preview';
}
class FormInfo extends Object {
  public $columns;
  public $actions;
  public $struct;
  public $config;

  public function getTypes() {
    return array(
      'columns'  => 'integer',
      'actions'  => 'string',
      'struct'   => 'string',
      'config'   => 'array'
    );
  }

  public function __construct(array $init) {
    parent::__construct($init);
  }

}
