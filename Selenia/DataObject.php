<?php
namespace Selenia;

use ArrayIterator;
use Exception;
use Iterator;
use PDO;
use PDOStatement;
use PhpKit\ConnectionInterface;
use PhpKit\ExtPDO;
use ReflectionObject;
use Selenia\Exceptions\Fatal\DataModelException;
use Selenia\Exceptions\Flash\ValidationException;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\FlashType;

class QueryCache
{
  public $insertQuery;
  public $queryFieldNames;
  public $readQuery;
  public $updateQuery;
}

class DataObject
{
  const MSG_UNSUPPORTED = "A operação não foi implementada.";

  static         $cacheObj = [];
  private static $lastId;

  public $booleanFields;
  public $dateFields;
  public $dateTimeFields;
  public $disableValidation = false;
  public $fieldLabels;
  public $fieldNames;
  public $fileFields;
  public $filterFields;
  /**
   * Foreign keys. Only works with page modules.
   *
   * @var array of ForeignKey
   */
  public $fk = null;
  /**
   * @var string Ex: 'o' | 'a' for portuguese.
   */
  public $gender;
  public $imageFields;
  /**
   * @var string
   */
  public $plural;
  /** Table prefix for field names on SQL queries. */
  public $prefix = 'T';
  public $primaryKeyName; //array of singletons for all subclasses
  public $primarySortField;
  public $requiredFields;
  /**
   * @var string
   */
  public $singular;
  public $tableName;
  public $titleField;

  /** @var ExtPDO */
  protected $pdo;

  function __construct (ConnectionInterface $con)
  {
    $this->pdo = $con->getPdo ();
  }

  static function getNewPrimaryKeyValue ()
  {
    $id           = self::$lastId;
    self::$lastId = null;
    return $id;
  }

  static function now ()
  {
    return date ('Y-m-d H:i:s');
  }

  static function serializePropertiesToXML (array $data, array $fields, $tag = 'e')
  {
    $output = "<$tag>";
    foreach ($fields as $fieldInfo) {
      $info      = explode (':', $fieldInfo);
      $fieldName = $info[0];
      if (count ($info) < 2)
        $alias = $fieldName;
      else $alias = $info[1];
      if (isset($data[$fieldName])) {
        $output .= "<$alias>";
        $value = $data[$fieldName];
        if (is_numeric ($value))
          $output .= $value;
        else if (is_bool ($value))
          $output .= $value ? 'true' : 'false';
        else if (is_string ($value))
          $output .= htmlspecialchars ($value);
        $output .= "</$alias>";
      }
    }
    return $output . "</$tag>";
  }

  static function today ()
  {
    return date ('Y-m-d');
  }

  static function validateDate ($date)
  {
    return empty($date) || (preg_match ('#^\d{4}-\d{2}-\d{2}$#', $date) && strtotime ($date));
  }

  static function validateDateTime ($date)
  {
    return empty($date) || (preg_match ('#^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})?$#', $date) && strtotime ($date));
  }

  function __sleep ()
  {
    return $this->fieldNames;
  }

  /**
   * Returns all records from the corresponding database table.
   * An alias for {@see query()}.
   *
   * @return array
   */
  function all ()
  {
    return $this->query ();
  }

  /**
   * Removes the record in the database that matches this intance's primary key value.
   * Note: any associated files/images are also deleted.
   *
   * @throws Exception
   * @global Application $application
   */
  function delete ()
  {
    $this->pdo->beginTransaction ();
    try {
      $this->beforeDelete ();
      $this->pdo->query ("DELETE FROM $this->tableName WHERE $this->primaryKeyName=?",
        [$this->getPrimaryKeyValue ()]);

      //delete linked resources

      if (isset($this->imageFields))
        foreach ($this->imageFields as $field)
          $this->deleteImage ($field);
      if (isset($this->fileFields))
        foreach ($this->fileFields as $field)
          $this->deleteFile ($field);

      $this->afterDelete ();
      $this->pdo->commit ();
    }
    catch (Exception $e) {
      $this->pdo->rollback ();
      throw $e;
    }
  }

  /**
   * Loads the record with the given id into the model object.
   * If the record is not found, the instance is not modified, which allows the insertion of a new record.
   *
   * @param $id
   * @return $this
   * @throws DataModelException
   */
  function find ($id)
  {
    $this->setPrimaryKeyValue ($id);
    $this->read ();
    return $this;
  }

  /**
   * Returns the values currently saved on the database for the record with the
   * same primary key value as this object's primary key.
   *
   * @return DataObject
   */
  function getCurrentValues ()
  {
    if (!$this->isNew ()) {
      $class   = new ReflectionObject($this);
      $current = $class->newInstance ();
      $current->setPrimaryKeyValue ($this->getPrimaryKeyValue ());
      $current->read ();
      return $current;
    }
    return null;
  }

  function getPrimaryKeyValue ()
  {
    $name = $this->primaryKeyName;
    if (empty($name)) return null;
    return property ($this, $name);
  }

  function getRequestedPrimaryKeyValue ()
  {
    $pk = $this->getPrimaryKeyValue ();
    if (isset($pk))
      return $pk;
    if ($this->isInstanceRequested ())
      return $_REQUEST[$this->primaryKeyName];
    return null;
  }

  function getTitle ($default = '')
  {
    $title = isset($this->titleField) ? $this->{$this->titleField} : ''; //$this->getPrimaryKeyValue()
    return strlen ($title) ? $title : $default;
  }

  /**
   * Returns a map of field names and the corresponding values.
   *
   * @return array
   */
  function getValues ()
  {
    $o = [];
    foreach ($this->fieldNames as $k)
      $o[$k] = $this->$k;
    return $o;
  }

  function initFromQueryString ()
  {
    $fields = [];
    if (isset($this->primaryKeyName))
      $fields[] = $this->primaryKeyName;
    if (isset($this->filterFields))
      $fields = array_merge ($fields, $this->filterFields);
    // may be overriden
    foreach ($fields as $field) {
      $param = safeParameter ($field);
      if (isset($param))
        $this->$field = $param;
    }
  }

  /**
   * Inserts the instance's fields into a new record in the database.
   * Note: the primary key value is included for it will only be null for autoincrement or trigger-filled fields.
   *
   * @param bool $insertFiles
   * @throws Exception
   * @throws ValidationException
   */
  function insert ($insertFiles = true)
  {
    $this->validate (true);
    $cache = $this->cache ();
    $this->pdo->beginTransaction ();
    try {
      if ($insertFiles) {
        if (isset($this->imageFields))
          foreach ($this->imageFields as $field)
            $this->handleImageInsert ($field);
        if (isset($this->fileFields))
          foreach ($this->fileFields as $field)
            $this->handleFileInsert ($field);
      }
      if (!isset($cache->insertQuery)) {
        foreach ($this->fieldNames as $k) {
          $names[] = $k;
          /*          if ($this->isDate($k) || $this->isDateTime($k)) {
                      $markers[] = 'JULIANDAY(?)';
                      $values[] = $this->encodeDate($this->$k);
                    }
                    else {*/
          $markers[] = '?';
          $values[]  = $this->fromPropertyToFieldValue ($k);
//          }
        }
        $markers            = implode (',', $markers);
        $fields             = '`' . implode ('`,`', $names) . '`';
        $cache->insertQuery = "INSERT INTO $this->tableName ($fields) VALUES ($markers)";
      }
      else foreach ($this->fieldNames as $k)
        $values[] = $this->fromPropertyToFieldValue ($k);
      $this->pdo->query ($cache->insertQuery, $values);
      self::$lastId = $this->pdo->lastInsertId ();
      $this->pdo->commit ();
      if (isset($this->primaryKeyName)) {
        $keyValue = $this->getPrimaryKeyValue ();
        if (strlen ($keyValue) == 0)
          $this->setPrimaryKeyValue ($this->getNewPrimaryKeyValue ());
      }
    }
    catch (Exception $e) {
      $this->pdo->rollback ();
      throw $e;
    }
  }

  function isInstanceRequested ()
  {
    return isset($_REQUEST[$this->primaryKeyName]);
  }

  function isModified ()
  {
    $current = $this->getCurrentValues ();
    if (is_null ($current))
      return true;
    else foreach ($this->fieldNames as $field)
      if ($this->$field != $current->$field)
        return true;
    return false;
  }

  function isNew ()
  {
    $v = $this->getPrimaryKeyValue ();
    return is_null ($v) || $v === ''; //don't use empty()
  }

  /**
   * @param array|Iterator $data
   * @param callable       $callback
   */
  function iterate ($data, callable $callback)
  {
    if (is_array ($data))
      $data = new ArrayIterator($data);
    while ($data->valid ()) {
      $this->loadFrom ($data->current ());
      call_user_func ($callback, $this, $data->key ());
      $data->next ();
    }
  }

  /**
   * Generates and executes an SQL query with joined tables.
   *
   * @param array  $joins
   * @param string $where
   * @param array  $params
   * @param string $orderBy
   * @return PDOStatement
   */
  function join (array $joins, $where = '', array $params = null, $orderBy = '')
  {
    $o  = [];
    $ch = 'B';
    foreach ($joins as $field => $modelClass) {
      $m   = new $modelClass;
      $o[] = "LEFT JOIN $m->tableName $ch ON A.$field=$ch.$m->primaryKeyName";
      ++$ch;
    }
    $joinClauses = implode ("\n", $o);
    $where       = $where ? "WHERE $where" : '';
    $orderBy     = $orderBy ?: $this->primarySortField;
    $orderBy     = $orderBy ? "ORDER BY $orderBy" : '';
    return $this->pdo->query (
      "SELECT * FROM $this->tableName A $where
        $joinClauses
        $orderBy", $params);
  }

  /**
   * Loads this instance's properties with values read from the supplied database struct.
   *
   * @param array $record
   */
  function loadFrom (array $record = null)
  {
    if (!is_null ($record)) {
      foreach ($record as $name => $value)
        $this->setPropertyFromFieldValue ($name, $value);
    }
  }

  /**
   * @param array|Iterator $data
   * @param callable       $callback
   * @return array
   */
  function map ($data, callable $callback)
  {
    if (is_array ($data))
      $data = new ArrayIterator($data);
    $o = [];
    while ($data->valid ()) {
      $this->loadFrom ($data->current ());
      $o [] = call_user_func ($callback, $this, $data->key ());
      $data->next ();
    }
    return $o;
  }

  /**
   * Executes the most common select type query for this kind of data.
   * Defauls to returning all fields from all records.
   *
   * @return array
   */
  function query ()
  {
    return $this->queryAllFields ($this->primarySortField);
  }

  function queryAllFields ($sortBy = '')
  {
    if (!empty($sortBy))
      $sortBy = " ORDER BY $sortBy";
    $fields = $this->getQueryFieldNames ();
    list ($where, $values) = $this->getFilterSQLAndValues ();
    return $this->hidrate ($this->pdo->query ("SELECT $fields FROM {$this->tableName} $this->prefix$where$sortBy",
      $values));
  }

  function queryAsXML ($fields = null, $rootTag = 'data', $rowTag = 'e')
  {
    $st     = $this->query ();
    $result = "<$rootTag>";
    if (is_null ($fields))
      $fields = $this->fieldNames;
    while (($values = $st->fetch (PDO::FETCH_ASSOC)) !== false)
      $result .= self::serializePropertiesToXML ($values, $fields, $rowTag);
    return $result . "</$rootTag>";
  }

  /**
   *
   * @param string $condition
   * @param string $fields
   * @param string $sortBy comma delimited field list. NULL for no sorting. Empty string for default sorting.
   * @param array  $params
   * @param string $limit
   * @return PDOStatement
   */
  function queryBy ($condition, $fields = '', $sortBy = '', array $params = null, $limit = '')
  {
    list ($where, $values) = $this->getFilterSQLAndValues ();
    if (!empty($condition))
      $where = $where != '' ? "$where AND ($condition)" : " WHERE $condition";
    if (!is_null ($params))
      $values = array_merge ($values, $params);
    if (empty($fields))
      $fields = $this->getQueryFieldNames ();
    if (!is_null ($sortBy) && empty($sortBy) && isset($this->primarySortField))
      $sortBy = $this->primarySortField;
    if (!empty($sortBy))
      $sortBy = " ORDER BY $sortBy";
    return $this->hidrate ($this->pdo->query ("SELECT $fields FROM {$this->tableName} $this->prefix$where$sortBy $limit",
      $values));
  }

  function read ($keyName = null)
  {
    $cache = $this->cache ();
    if (isset($keyName)) {
      $keyValue = get ($_REQUEST, $keyName, $this->$keyName);
    }
    else {
      $keyName  = $this->primaryKeyName;
      $keyValue = $this->getRequestedPrimaryKeyValue ();
    }
    //if (!isset($cache->readQuery))
    $cache->readQuery =
      'SELECT ' . $this->getQueryFieldNames () . " FROM $this->tableName $this->prefix WHERE $keyName=? LIMIT 1";
    $record           = $this->pdo->query ($cache->readQuery, [$keyValue])->fetch (PDO::FETCH_ASSOC);
    if ($record !== false) {
      $this->loadFrom ($record);
      return true;
    }
    return false;
  }

  /**
   * Loads the data object's fields from data sent on the HTTP request (either POST or GET).
   * If $allowedFields is specified, only those fields will be loaded from the request,
   * otherwhise all the object's fields will be loaded.
   * Empty fields on the request will be stored as NULL values.
   * Nonexisting fields on the request will not be stored, except if they are boolean fields
   * (ex. from checkbox HTML fields).
   * The loaded values will be escaped to avoid SQL injection. HTML content is not modified.
   *
   * @param array $data          The request data.
   * @param array $allowedFields A list of field names to load.
   */
  function safeLoadFrom (array $data, array $allowedFields = null)
  {
    if (isset($allowedFields)) {
      foreach ($allowedFields as $name)
        if (array_key_exists ($name, $data))
          $this->setPropertyFromFieldValue ($name, $data[$name]);
    }
    else foreach ($this->fieldNames as $name)
      if (array_key_exists ($name, $data))
        $this->setPropertyFromFieldValue ($name, $data[$name]);
      else if ($this->isBoolean ($name))
        $this->setPropertyFromFieldValue ($name, 0);
  }

  function save ($insertFiles = true)
  {
    if ($this->isNew ()) $this->insert ($insertFiles);
    else $this->update ();
  }

  function serialize (array $fieldNames = null)
  {
    if (is_null ($fieldNames))
      $fieldNames = $this->fieldNames;
    $fields = [];
    foreach ($fieldNames as $k)
      $fields[] = "$k=" . str_replace (',', '§', $this->$k);
    return implode (',', $fields);
  }

  function serializeToJSON (array $fields)
  {
    $output = '{';
    $sep    = false;
    foreach ($fields as $fieldInfo) {
      if ($sep) $output .= ',';
      else $sep = true;
      $info      = explode (':', $fieldInfo);
      $fieldName = $info[0];
      if (count ($info) < 2)
        $alias = $fieldName;
      else $alias = $info[1];
      $output .= '"' . $alias . '":';
      $value = $this->$fieldName;
      if (is_numeric ($value)) $output .= $value;
      else if (is_bool ($value)) $output .= $value ? 'true' : 'false';
      else if (is_string ($value)) $output .= '"' . str_replace (["\r", "\n", '"'], ['\n', '', '\"'], $value) . '"';
      else $output .= 'null';
    }
    return $output . '}';
  }

  function serializeToXML (array $fields = null, $tag = 'e')
  {
    if (is_null ($fields))
      $fields = $this->fieldNames;
    return self::serializePropertiesToXML ((array)$this, $fields, $tag);
  }

  function setBoolField ($fieldName, $value, $filter = '')
  {

    if (!empty($filter))
      $filter = " WHERE $filter";
    $this->pdo->query (
      "UPDATE {$this->tableName} SET $fieldName=$value$filter"
    );
  }

  function setPrimaryKeyValue ($value)
  {
    if (isset($this->primaryKeyName))
      $this->{$this->primaryKeyName} = $value;
    else throw new DataModelException($this, "There is no primary key defined.");
  }

  function unserialize ($data)
  {
    $fields = explode (',', $data);
    foreach ($fields as $field) {
      list($k, $v) = explode ('=', $field, 2);
      $this->$k = str_replace ('§', ',', $v);
    }
  }

  /**
   * Saves the instance's fields into a record in the database.
   * Note: if no record exists yet, a new one is inserted, although you should use save() instead, for better
   * performance.
   */
  function update ()
  {
    $this->validate (false);
    $cache = $this->cache ();
    $this->pdo->beginTransaction ();
    try {
      $this->beforeUpdate ();
      if (isset($this->imageFields))
        foreach ($this->imageFields as $field)
          $this->handleImageUpdate ($field);
      if (isset($this->fileFields))
        foreach ($this->fileFields as $field)
          $this->handleFileUpdate ($field);
      if (!isset($cache->updateQuery)) {
        foreach ($this->fieldNames as $k)
          if ($k != $this->primaryKeyName) {
            /*            if ($this->isDate($k) || $this->isDateTime($k)) {
                          $list[] = "$k=JULIANDAY(?)";
                          $values[] = $this->encodeDate($this->$k);
                        }
                        else {*/
            $list[]   = '`' . $k . '`' . '=?';
            $values[] = $this->fromPropertyToFieldValue ($k);
//            }
          }
        $fieldList          = implode (',', $list);
        $cache->updateQuery = "UPDATE $this->tableName SET $fieldList WHERE $this->primaryKeyName=?";
      }
      else foreach ($this->fieldNames as $k)
        if ($k != $this->primaryKeyName)
          $values[] = $this->fromPropertyToFieldValue ($k);
      $values[] = $this->getPrimaryKeyValue ();
      $count    = $this->pdo->query ($cache->updateQuery, $values)->rowCount ();
      $this->afterUpdate ();
      $this->pdo->commit ();
    }
    catch (Exception $e) {
      $this->pdo->rollback ();
      throw $e;
    }
    if (!$count) //if there's no record with the specified key, create one, but do not insert the same files again
      $this->insert (false);
  }

  function validate ($forInsert = false)
  {
    if ($this->disableValidation)
      return;
    if (isset($this->requiredFields)) {
      foreach ($this->requiredFields as $field)
        if (!isset($this->$field) || $this->$field === '')
          if (!$this->isImage ($field) && !Media::isFileUploaded ($field . '_file'))
            throw new ValidationException(ValidationException::REQUIRED_FIELD,
              get ($this->fieldLabels, $field, $field));
    }
    if (isset($this->dateFields)) {
      foreach ($this->dateFields as $field)
        if (isset($this->$field) && $this->$field !== '' && !self::validateDate ($this->$field))
          throw new ValidationException(ValidationException::INVALID_DATE, get ($this->fieldLabels, $field, $field));
    }
    if (isset($this->dateTimeFields)) {
      foreach ($this->dateTimeFields as $field)
        if (isset($this->$field) && $this->$field !== '' && !self::validateDateTime ($this->$field))
          throw new ValidationException(ValidationException::INVALID_DATETIME,
            get ($this->fieldLabels, $field, $field));
    }
  }

  /**
   * Returns records matching a filter.
   *
   * @param        $condition
   * @param array  $params
   * @param string $orderBy
   * @param string $limit
   * @return PDOStatement
   */
  function where ($condition, array $params = null, $orderBy = '', $limit = '')
  {
    return $this->queryBy ($condition, '', $orderBy, $params, $limit);
  }

  /**
   * Method called right after the deletion is done but before the transaction ends.
   */
  protected function afterDelete ()
  {
    //override
  }

  /**
   * Method called right after the update is done but before the transaction ends.
   */
  protected function afterUpdate ()
  {
    //override
  }

  /**
   * Method called right after the transaction begins but before any deletion is made.
   */
  protected function beforeDelete ()
  {
    //override
  }

  /**
   * Method called right after the transaction begins but before any update is made.
   */
  protected function beforeUpdate ()
  {
    //override
  }

  /**
   * Returns SQL query information for this class.
   *
   * @return QueryCache
   */
  protected function cache ()
  {
    $c = get_class ($this);
    if (isset(self::$cacheObj[$c]))
      return self::$cacheObj[$c];
    return self::$cacheObj[$c] = new QueryCache();
  }

  protected function deleteFile ($fieldName)
  {
    if (!property_exists ($this, $fieldName))
      throw new DataModelException($this, "Undefined field $fieldName.");
    if (isset($this->$fieldName)) {
      Media::deleteFile ($this->$fieldName);
      $this->$fieldName = null;
    }
  }

  protected function deleteGallery ()
  {
    $id = $this->getPrimaryKeyValue ();
    if (isset($id)) {
      $data = $this->pdo->query (
        "SELECT id FROM Images WHERE gallery=?",
        [$id]
      )->fetchAll (PDO::FETCH_NUM);
      foreach ($data as $record)
        Media::deleteImage ($record[0]);
    }
  }

  protected function deleteImage ($fieldName)
  {
    if (!property_exists ($this, $fieldName))
      throw new DataModelException($this, "Undefined field $fieldName.");
    if (isset($this->$fieldName)) {
      Media::deleteImage ($this->$fieldName);
      $this->$fieldName = null;
    }
  }

  protected function getQueryFieldNames ()
  {
    $cache = $this->cache ();
    if (isset($cache->queryFieldNames))
      return $cache->queryFieldNames;
    $queryFieldNames = [];
    foreach ($this->fieldNames as $field)
      /*      if ($this->isDate($field))
              $queryFieldNames[] = "STRFTIME('%Y-%m-%d',$this->prefix.$field) $field";
            else if ($this->isDateTime($field))
              $queryFieldNames[] = "STRFTIME('%Y-%m-%d %H:%M:%S',$this->prefix.$field) $field";
            else */
      $queryFieldNames[] = $this->prefix . '.' . $field . ' `' . $field . '`';
    $cache->queryFieldNames = implode (',', $queryFieldNames);
    return $cache->queryFieldNames;
  }

  protected function isBoolean ($fieldName)
  {

    return isset($this->booleanFields) && array_search ($fieldName, $this->booleanFields) !== false;
  }

  protected function isDate ($fieldName)
  {
    return isset($this->dateFields) && array_search ($fieldName, $this->dateFields) !== false;
  }

  protected function isDateTime ($fieldName)
  {
    return isset($this->dateTimeFields) && array_search ($fieldName, $this->dateTimeFields) !== false;
  }

  protected function isFile ($fieldName)
  {
    return isset($this->fileFields) && array_search ($fieldName, $this->fileFields) !== false;
  }

  protected function isImage ($fieldName)
  {
    return isset($this->imageFields) && array_search ($fieldName, $this->imageFields) !== false;
  }

  protected function unsupported ()
  {
    throw new FlashMessageException(self::MSG_UNSUPPORTED, 0, FlashType::ERROR);
  }

  /**
   * Converts a property's value into a suitable value to be stored in a database.
   *
   * @param string $fieldName
   * @return mixed
   */
  private function fromPropertyToFieldValue ($fieldName)
  {
    $value = property ($this, $fieldName);
    if ($this->isBoolean ($fieldName))
      return $value ? 1 : 0;
    if (is_null ($value) || $value === '')
      return null;
    if (is_numeric ($value)) {
      $i = intval ($value);
      if ($i == $value)
        return $i;
      $f = floatval ($value);
      if ($f == $value)
        return $f;
    }
    return $value;
  }

  private function getFilterSQLAndValues ($prefix = ' WHERE ')
  {
    if (isset($this->filterFields)) {
      $where  = '';
      $values = [];
      foreach ($this->filterFields as $field) {
        $value = property ($this, $field);
        if (isset($value)) {
          $values[] = $value;
          $where .= ($where != '' ? ' AND ' : $prefix) . "$this->prefix.$field=?";
        }
      }
      return [$where, $values];
    }
    return ['', []];
  }

  private function handleFileInsert ($fileFieldName)
  {
    $this->$fileFieldName = Media::insertUploadedFile ($fileFieldName);
  }

  private function handleFileUpdate ($fileFieldName)
  {
    $fileFormFieldName = $fileFieldName . '_file';
    $current           = $this->getCurrentValues ();
    if (Media::isFileUploaded ($fileFormFieldName)) {
      if (isset($current->$fileFieldName))
        Media::deleteFile ($current->$fileFieldName);
      $this->handleFileInsert ($fileFieldName);
    }
    else if (is_null ($this->$fileFieldName) && isset($current->$fileFieldName))
      Media::deleteFile ($current->$fileFieldName);
  }

  private function handleImageInsert ($imageFieldName)
  {
    $this->$imageFieldName = Media::insertUploadedImage ($imageFieldName);
  }

  private function handleImageUpdate ($imageFieldName)
  {
    $fileFormFieldName = $imageFieldName . '_file';
    $current           = $this->getCurrentValues ();
    if (Media::isFileUploaded ($fileFormFieldName)) {
      Media::checkFileIsValidImage ($fileFormFieldName);
      if (isset($current->$imageFieldName))
        Media::deleteImage ($current->$imageFieldName);
      $this->handleImageInsert ($imageFieldName);
    }
    else if (is_null ($this->$imageFieldName) && isset($current->$imageFieldName))
      Media::deleteImage ($current->$imageFieldName);
    else if (isset($this->$imageFieldName))
      Media::updateImageInfo ($this->$imageFieldName, null, null);
  }

  private function hidrate (PDOStatement $st)
  {
    global $lastModel;
    $lastModel = $this;
    return $st->fetchAll ();
    //TODO: hidrate data
    //$this->_data = $st->fetchAll (PDO::FETCH_CLASS, get_class ($this));
  }

  /**
   * Loads a property with a value read from a database.
   *
   * @param string $fieldName
   * @param mixed  $value
   */
  private function setPropertyFromFieldValue ($fieldName, $value)
  {
    if ($this->isBoolean ($fieldName))
      switch ($value) {
        case 'false':
          $value = false;
          break;
        case 'true':
          $value = true;
          break;
        default:
          $value = (boolean)$value;
      }
    elseif ($value === '')
      $value = null;
    $this->$fieldName = $value;
  }

}
