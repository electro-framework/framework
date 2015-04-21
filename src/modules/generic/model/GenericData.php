<?php
class GenericData extends DataObject {
  public $id;
  public $key;
  public $subkey;
  public $parent;
  public $title;
  public $text;
  public $text2;
  public $text3;
  public $text4;
  public $bool;
  public $bool2;
  public $bool3;
  public $bool4;
  public $image;
  public $image2;
  public $image3;
  public $image4;
  public $date;
  public $date2;
  public $datetime;
  public $datetime2;
  public $file;
  public $file2;
  public $number;
  public $number2;
  public $field1;
  public $field2;
  public $field3;
  public $field4;

  public $fieldNames = array(
    'id',
    'key',
    'subkey',
    'parent',
    'title',
    'text',
    'text2',
    'text3',
    'text4',
    'bool',
    'bool2',
    'bool3',
    'bool4',
    'image',
    'image2',
    'image3',
    'image4',
    'date',
    'date2',
    'datetime',
    'datetime2',
    'file',
    'file2',
    'number',
    'number2',
    'field1',
    'field2',
    'field3',
    'field4'
  );
  public $imageFields = array(
    'image',
    'image2',
    'image3',
    'image4'
  );
  public $dateFields = array(
    'date',
    'date2'
  );
  public $dateTimeFields = array(
    'datetime',
    'datetime2'
  );
  public $fileFields = array(
    'file',
    'file2'
  );
  public $booleanFields = array(
    'bool',
    'bool2',
    'bool3',
    'bool4'
  );
  public $primaryKeyName = 'id';
  public $tableName = 'Generic';
  public $primarySortField = 'date DESC,id';
  public $titleField = 'title';
  public $filterFields = array('key','subkey','parent');

}
