<?php
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\DataSet;
use Selene\Matisse\Exceptions\ComponentException;

class ModelAttributes extends ComponentAttributes
{
  public $name;
  public $of;
  public $type = 'record';
  public $value;
  public $key  = 'id';
  public $filter;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_of () { return AttributeType::ID; }
  protected function typeof_type () { return AttributeType::TEXT; }
  protected function enum_type () { return ['list', 'record']; }
  protected function typeof_key () { return AttributeType::ID; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_filter () { return AttributeType::TEXT; }
}

class Model extends Component implements IAttributes
{
  /**
   * Returns the component's attributes.
   * @return ModelAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ModelAttributes
   */
  public function newAttributes ()
  {
    return new ModelAttributes($this);
  }

  public function parsed ()
  {
    global $controller, $model;
    $isDefault = !isset($this->attrs ()->name);
    $name      = $isDefault ? 'default' : $this->attrs ()->name;
    $of        = $this->attrs ()->get ('of');
    if (!isset($of))
      throw new ComponentException($this, "Attribute <b>of</b> is required.");
    $type      = $this->attrs ()->type;
    $modelItem = get ($model, $of);
    if (!isset($modelItem))
      throw new ComponentException($this, "Model <b>$of</b> is not defined.");
    $dataClass = property ($modelItem, 'class');
    if (!isset($dataClass))
      throw new ComponentException($this, "No data class defined for model <b>$of</b>.");
    if (!isset($modelItem->module))
      throw new ComponentException($this, "No module defined for model <b>$of</b>.");
    $x = ModuleLoader::searchAndLoadClass ($dataClass, $modelItem->module);
    if (!$x)
      throw new ComponentException($this, "Can't create an instance of class <b>$of</b>.");
    $dataObj = newInstanceOf ($dataClass);
    switch ($type) {
      case 'list':
        $filter  = $this->attrs ()->get ('filter', '');
        $data    = $dataObj->queryBy ($filter)->fetchAll ();
        $dataSrc = new DataSet($data);
        break;
      case 'record':
        $key           = $this->attrs ()->key;
        $dataObj->$key = $this->attrs ()->get ('value');
        $data          = $dataObj->query ()->fetch ();
        $dataSrc       = new DataRecord($data);
    }
    $controller->setDataSource ($name, $dataSrc, $isDefault);
  }
}
