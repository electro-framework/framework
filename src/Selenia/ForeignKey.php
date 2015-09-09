<?php
namespace Selenia;

class FKDeleteAction {
  const DELETE_RECORD = 1;
  const SET_KEY_TO_NULL = 2;
  const DENY = 3;
}

class ForeignKey extends Object {
  public $class;
  public $module;
  public $key;
  public $field;
  public $action;

  public function getTypes() {
    return array(
      'class'  => 'string',
      'module' => 'string',
      'key'    => 'string',
      'field'  => 'string',
      'action' => 'integer'
    );
  }

  public function __construct(array $init) {
    parent::__construct($init);
  }

}
