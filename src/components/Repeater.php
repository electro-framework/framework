<?php
namespace impactwave\matisse\components;
use impactwave\matisse\AttributeType;
use impactwave\matisse\Component;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\IAttributes;

class RepeaterAttributes extends ComponentAttributes
{
  public $repeat;
  public $glue;
  public $no_data;
  public $data;
  public $header;
  public $footer;
  public $count;
  public $consume = true;
  public $rewind  = true;

  protected function typeof_repeat () { return AttributeType::SRC; }
  protected function typeof_header () { return AttributeType::SRC; }
  protected function typeof_footer () { return AttributeType::SRC; }
  protected function typeof_glue () { return AttributeType::SRC; }
  protected function typeof_no_data () { return AttributeType::SRC; }
  protected function typeof_data () { return AttributeType::DATA; }
  protected function typeof_count () { return AttributeType::NUM; }
  protected function typeof_consume () { return AttributeType::BOOL; }
  protected function typeof_rewind () { return AttributeType::BOOL; }
}

class Repeater extends Component implements IAttributes
{

  /**
   * Returns the component's attributes.
   * @return RepeaterAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return RepeaterAttributes
   */
  public function newAttributes ()
  {
    return new RepeaterAttributes($this);
  }

  protected function render ()
  {
    $count                   = $this->attrs ()->get ('count');
    $consume                 = $this->attrs ()->get ('consume');
    $this->defaultDataSource = $this->attrs ()->get ('data');
    if (isset($this->defaultDataSource)) {
      if (is_array ($this->defaultDataSource))
        $this->defaultDataSource = new DataSet($this->defaultDataSource);
      $dataIter = $this->defaultDataSource->getIterator ();
      if ($this->attrs ()->rewind)
        $dataIter->rewind ();
      $template = $this->getChildren ('repeat');
      if ($dataIter->valid () && isset($template)) {
        $glue  = $this->getChildren ('glue');
        $first = true;
        $this->runSet ($this->getChildren ('header'));
        do {
          if ($first)
            $first = false;
          else if (isset($glue))
            $this->runSet ($glue);
          $this->runSet ($template);
          if ($consume)
            $dataIter->next ();
        } while ($dataIter->valid () && (!isset($count) || --$count > 0));
        $this->runSet ($this->getChildren ('footer'));
        return;
      }
    }
    if (isset($count)) {
      $template = $this->getChildren ('repeat');
      if (isset($template)) {
        $glue = $this->getChildren ('glue');
        for ($i = 0; $i < $count; ++$i) {
          if ($i == 0)
            $this->runSet ($this->getChildren ('header'));
          if ($i > 0 && isset($glue))
            $this->runSet ($glue);
          $this->runSet ($template);
        }
        if ($i > 0) {
          $this->runSet ($this->getChildren ('footer'));
          return;
        }
      }
    }
    if (isset($this->attrs ()->no_data)) {
      $this->setChildren ($this->getChildren ('no_data'));
      $this->runChildren ();
    }
  }

}
