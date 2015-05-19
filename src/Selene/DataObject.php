<?php
namespace Selene;

use ArrayIterator;
use Exception;
use Iterator;
use PDO;
use PDOStatement;
use ReflectionObject;
use Selene\Exceptions\BaseException;
use Selene\Exceptions\DataModelException;
use Selene\Exceptions\Status;
use Selene\Exceptions\ValidationException;

class QueryCache
{
  public $readQuery;
  public $insertQuery;
  public $updateQuery;
  public $queryFieldNames;
}

class DataObject
{
  const MSG_UNSUPPORTED = "A operação não foi implementada.";
  public static  $cacheObj = [];
  private static $lastId;
  public         $fieldNames;
  public         $primaryKeyName;
  public         $titleField;
  public         $tableName;
  public         $primarySortField;
  public         $requiredFields;
  public         $fieldLabels;
  public         $dateFields;
  public         $dateTimeFields;
  public         $imageFields;
  public         $fileFields;
  public         $booleanFields;
  public         $filterFields;
  /**
   * Foreign keys. Only works with page modules.
   * @var array of ForeignKey
   */
  public $fk                = null; //array of singletons for all subclasses
  public $disableValidation = false;
  /** Table prefix for field names on SQL queries. */
  public $prefix = 'T';
  /**
   * @var string Ex: 'o' | 'a' for portuguese.
   */
  public $gender;
  /**
   * @var string
   */
  public $singular;
  /**
   * @var string
   */
  public $plural;

  function __construct ($keyValue = null)
  {
    if (isset($keyValue))
      $this->setPrimaryKeyValue ($keyValue);
    /*
        if (isset($this->dateFields))
            foreach ($this->dateFields as $field)
                if ($field != $this->primaryKeyName)
                    $this->$field = date('Y-m-d');*/
  }

  public static function encodeDate ($date)
  {
    //return preg_replace('#(\\d{2})-(\\d{2})-(\\d{4})#','$3-$2-$1',$date);
    return $date;
  }

  public static function validateDate ($date)
  {
    return empty($date) || (preg_match ('#^\d{4}-\d{2}-\d{2}$#', $date) && strtotime ($date));
  }

  public static function validateDateTime ($date)
  {
    return empty($date) || (preg_match ('#^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}:\d{2})?$#', $date) && strtotime ($date));
  }

  public static function today ()
  {
    return date ('Y-m-d');
  }

  public static function now ()
  {
    return date ('Y-m-d H:i:s');
  }

  public static function julianDay ($date)
  {
    return $date;
    //return floatval(database_get("SELECT JULIANDAY(?)",array($date)));
  }

  public static function daysDiff ($startDate, $endDate)
  {
    return intval (database_query ("SELECT JULIANDAY(?)-JULIANDAY(?)", [$endDate, $startDate])->fetchColumn (0));
  }

  public static function dateIsBefore ($date, $compareTo)
  {
    return intval (database_query ("SELECT JULIANDAY(?)-JULIANDAY(?)", [$date, $compareTo])->fetchColumn (0)) < 0;
  }

  public static function dateIsAfter ($date, $compareTo)
  {
    return intval (database_query ("SELECT JULIANDAY(?)-JULIANDAY(?)", [$date, $compareTo])->fetchColumn (0)) > 0;
  }

  public static function nextDay ($date)
  {
    return date ('Y-m-d', strtotime ('+1 day', strtotime ($date)));
  }

  public static function prevDay ($date)
  {
    return date ('Y-m-d', strtotime ('-1 day', strtotime ($date)));
  }

  public static function nextMonth ($date)
  {
    return date ('Y-m-d', strtotime ('+1 month', strtotime ($date)));
  }

  public static function addInterval ($date, $delta = '+1 year')
  {
    return date ('Y-m-d', strtotime ($delta, strtotime ($date)));
  }

  public static function checkEmail ($email)
  {

    return preg_match ('#^[\w\-\.]+@[\w\-\.]+\.\w{1,5}$#', $email) == 1;
  }

  public static function validateEmail ($fieldName, $email)
  {

    if (!self::checkEmail ($email))
      throw new ValidationException(ValidationException::INVALID_EMAIL, $fieldName);
  }

  public static function getNewPrimaryKeyValue ()
  {
    $id           = self::$lastId;
    self::$lastId = null;
    return $id;
  }

  public static function serializePropertiesToXML (array $data, array $fields, $tag = 'e')
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

  /**
   * Loads the record with the given id into the model object.
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
   * Returns all records from the corresponding database table.
   * An alias for {@see query()}.
   * @return PDOStatement
   */
  function all ()
  {
    return $this->query ();
  }

  /**
   * Sets the specified field to the given value.
   *
   * Can be used for chaining calls.
   * @param string $field
   * @param mixed  $value
   * @return $this
   */
  function set ($field, $value)
  {
    $this->$field = $value;
    return $this;
  }

  function getTitle ($default = '')
  {
    $title = isset($this->titleField) ? $this->{$this->titleField} : ''; //$this->getPrimaryKeyValue()
    return strlen ($title) ? $title : $default;
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
   * Converts a property's value into a suitable value to be stored in a database.
   * @param string $fieldName
   * @return mixed
   */
  function fromPropertyToFieldValue ($fieldName)
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

  /**
   * Loads a property with a value read from a database.
   * @param string $fieldName
   * @param mixed  $value
   */
  function setPropertyFromFieldValue ($fieldName, $value)
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
      'SELECT ' . $this->getQueryFieldNames () . " FROM $this->tableName $this->prefix WHERE $keyName=?";
    $record           = database_query ($cache->readQuery, [$keyValue])->fetch (PDO::FETCH_ASSOC);
    if ($record !== false) {
      $this->loadFrom ($record);
      return true;
    }
    return false;
  }

  /**
   * Inserts the instance's fields into a new record in the database.
   * Note: the primary key value is included for it will only be null for autoincrement or trigger-filled fields.
   * @param bool $insertFiles
   * @throws Exception
   * @throws ValidationException
   */
  function insert ($insertFiles = true)
  {
    global $db;
    $this->validate (true);
    $cache = $this->cache ();
    database_begin ();
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
      database_query ($cache->insertQuery, $values);
      self::$lastId = $db->lastInsertId ();
      database_commit ();
      if (isset($this->primaryKeyName)) {
        $keyValue = $this->getPrimaryKeyValue ();
        if (strlen ($keyValue) == 0)
          $this->setPrimaryKeyValue ($this->getNewPrimaryKeyValue ());
      }
    } catch (Exception $e) {
      database_rollback ();
      throw $e;
    }
  }

  /**
   * Saves the instance's fields into a record in the database.
   * Note: if no record exists yet, a new one is inserted.
   */
  function update ()
  {
    $this->validate (false);
    $cache = $this->cache ();
    database_begin ();
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
      $count    = database_query ($cache->updateQuery, $values)->rowCount ();
      $this->afterUpdate ();
      database_commit ();
    } catch (Exception $e) {
      database_rollback ();
      throw $e;
    }
    if (!$count) //if there's no record with the specified key, create one, but do not insert the same files again
      $this->insert (false);
  }

  /**
   * Removes the record in the database that matches this intance's primary key value.
   * Note: any associated files/images are also deleted.
   * @global Application $application
   */
  function delete ()
  {
    database_begin ();
    try {
      $this->beforeDelete ();
      database_query ("DELETE FROM $this->tableName WHERE $this->primaryKeyName=?",
        [$this->getPrimaryKeyValue ()]);

      //delete linked resources

      if (isset($this->imageFields))
        foreach ($this->imageFields as $field)
          $this->deleteImage ($field);
      if (isset($this->fileFields))
        foreach ($this->fileFields as $field)
          $this->deleteFile ($field);

      //cascadade this operation to linked database records (defined by foreign keys)

      if (isset($this->fk)) {
        foreach ($this->fk as $fk) {
          if (isset($fk->module))
            ModuleLoader::searchAndLoadClass ($fk->class, $fk->module);
          $dataClass = $fk->class;
          $data      = new $dataClass();
          if (isset($fk->key))
            $data->key = $fk->key;
          if (!isset($data->primaryKeyName))
            $data->primaryKeyName = $fk->field;
          $list = $data->queryBy ("$fk->field=?", $data->primaryKeyName, null, [$this->getPrimaryKeyValue ()])
                       ->fetchAll (PDO::FETCH_NUM);
          for ($i = 0; $i < count ($list); ++$i) {
            $data->setPrimaryKeyValue ($list[$i][0]);
            $data->read ();
            switch ($fk->action) {
              case FKDeleteAction::DELETE_RECORD:
                $data->delete ();
                break;
              case FKDeleteAction::SET_KEY_TO_NULL:
                $data->{$fk->field} = null;
                $data->update ();
                break;
              case FKDeleteAction::DENY:
                throw new DataModelException($this,
                  "This data object is being referenced by another one of type $fk->class and cannot be deleted.");
            }
          }
        }
      }
      $this->afterDelete ();
      database_commit ();
    } catch (Exception $e) {
      database_rollback ();
      throw $e;
    }
  }

  /**
   * Executes the most common select type query for this kind of data.
   * Defauls to returning all fields from all records.
   * @return PDOStatement
   */
  function query ()
  {
    return $this->queryAllFields ($this->primarySortField);
  }

  function getFilterSQLAndValues ($prefix = ' WHERE ')
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

  function queryAllFields ($sortBy = '')
  {
    if (!empty($sortBy))
      $sortBy = " ORDER BY $sortBy";
    $fields = $this->getQueryFieldNames ();
    list ($where, $values) = $this->getFilterSQLAndValues ();
    return database_query ("SELECT $fields FROM {$this->tableName} $this->prefix$where$sortBy", $values);
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
    return database_query ("SELECT $fields FROM {$this->tableName} $this->prefix$where$sortBy $limit", $values);
  }

  function getPrimaryKeyValue ()
  {
    $name = $this->primaryKeyName;
    if (empty($name)) return null;
    return property ($this, $name);
  }

  function setPrimaryKeyValue ($value)
  {
    if (isset($this->primaryKeyName))
      $this->{$this->primaryKeyName} = $value;
    else throw new DataModelException($this, "There is no primary key defined.");
  }

  function isInstanceRequested ()
  {
    return isset($_REQUEST[$this->primaryKeyName]);
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

  function isNew ()
  {
    $v = $this->getPrimaryKeyValue ();
    return is_null ($v) || $v === ''; //don't use empty()
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
   * Loads this instance's properties with values read from the supplied database struct.
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
   * Loads the data object's fields from data sent on the HTTP request (either POST or GET).
   * If $allowedFields is specified, only those fields will be loaded from the request,
   * otherwhise all the object's fields will be loaded.
   * Empty fields on the request will be stored as NULL values.
   * Nonexisting fields on the request will not be stored, except if they are boolean fields
   * (ex. from checkbox HTML fields).
   * The loaded values will be escaped to avoid SQL injection. HTML content is not modified.
   * @param array $allowedFields A list of field names to load.
   */
  function loadFromHttpRequest (array $allowedFields = null)
  {
    if (isset($allowedFields)) {
      foreach ($allowedFields as $name)
        if (array_key_exists ($name, $_REQUEST))
          $this->setPropertyFromFieldValue ($name, $_REQUEST[$name]);
    }
    else foreach ($this->fieldNames as $name)
      if (array_key_exists ($name, $_REQUEST))
        $this->setPropertyFromFieldValue ($name, $_REQUEST[$name]);
      else if ($this->isBoolean ($name))
        $this->setPropertyFromFieldValue ($name, 0);
  }

  /**
   * Returns the values currently saved on the database for the record with the
   * same primary key value as this object's primary key.
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

  function setBoolField ($fieldName, $value, $filter = '')
  {

    if (!empty($filter))
      $filter = " WHERE $filter";
    database_query (
      "UPDATE {$this->tableName} SET $fieldName=$value$filter"
    );
  }

  function deleteImage ($fieldName)
  {
    if (!property_exists ($this, $fieldName))
      throw new DataModelException($this, "Undefined field $fieldName.");
    if (isset($this->$fieldName)) {
      Media::deleteImage ($this->$fieldName);
      $this->$fieldName = null;
    }
  }

  function deleteFile ($fieldName)
  {
    if (!property_exists ($this, $fieldName))
      throw new DataModelException($this, "Undefined field $fieldName.");
    if (isset($this->$fieldName)) {
      Media::deleteFile ($this->$fieldName);
      $this->$fieldName = null;
    }
  }

  function deleteGallery ()
  {
    $id = $this->getPrimaryKeyValue ();
    if (isset($id)) {
      $data = database_query (
        "SELECT id FROM Images WHERE gallery=?",
        [$id]
      )->fetchAll (PDO::FETCH_NUM);
      foreach ($data as $record)
        Media::deleteImage ($record[0]);
    }
  }

  function handleImageUpdate ($imageFieldName)
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

  function handleImageInsert ($imageFieldName)
  {
    $this->$imageFieldName = Media::insertUploadedImage ($imageFieldName);
  }

  function handleFileUpdate ($fileFieldName)
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

  function handleFileInsert ($fileFieldName)
  {
    $this->$fileFieldName = Media::insertUploadedFile ($fileFieldName);
  }

  /**
   * Returns a map of field names and the corresponding values.
   * @return array
   */
  function getValues ()
  {
    $o = [];
    foreach ($this->fieldNames as $k)
      $o[$k] = $this->$k;
    return $o;
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

  function serialize (array $fieldNames = null)
  {
    if (is_null ($fieldNames))
      $fieldNames = $this->fieldNames;
    $fields = [];
    foreach ($fieldNames as $k)
      $fields[] = "$k=" . str_replace (',', '§', $this->$k);
    return implode (',', $fields);
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
   * Returns SQL query information for this class.
   * @return QueryCache
   */
  protected function cache ()
  {
    $c = get_class ($this);
    if (isset(self::$cacheObj[$c]))
      return self::$cacheObj[$c];
    return self::$cacheObj[$c] = new QueryCache();
  }

  protected function isDate ($fieldName)
  {
    return isset($this->dateFields) && array_search ($fieldName, $this->dateFields) !== false;
  }

  protected function isDateTime ($fieldName)
  {
    return isset($this->dateTimeFields) && array_search ($fieldName, $this->dateTimeFields) !== false;
  }

  protected function isImage ($fieldName)
  {
    return isset($this->imageFields) && array_search ($fieldName, $this->imageFields) !== false;
  }

  protected function isFile ($fieldName)
  {
    return isset($this->fileFields) && array_search ($fieldName, $this->fileFields) !== false;
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

  /**
   * Method called right after the transaction begins but before any update is made.
   */
  protected function beforeUpdate ()
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
   * Method called right after the deletion is done but before the transaction ends.
   */
  protected function afterDelete ()
  {
    //override
  }

  protected function isBoolean ($fieldName)
  {

    return isset($this->booleanFields) && array_search ($fieldName, $this->booleanFields) !== false;
  }

  protected function unsupported ()
  {
    throw new BaseException(self::MSG_UNSUPPORTED, 0, Status::ERROR);
  }
}

