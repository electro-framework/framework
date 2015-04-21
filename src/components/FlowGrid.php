<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class FlowGridAttributes extends ComponentAttributes
{
  public $repeat;
  public $new_element;
  public $data;
  public $table_tag = true;

  protected function typeof_repeat () { return AttributeType::SRC; }
  protected function typeof_new_element () { return AttributeType::SRC; }
  protected function typeof_data () { return AttributeType::DATA; }
  protected function typeof_table_tag () { return AttributeType::BOOL; }
}

class FlowGrid extends VisualComponent
{

  private $cellNum = -1;
  private $rowNum  = -1;
  private $cols;

  /**
   * Returns the component's attributes.
   * @return FlowGridAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return FlowGridAttributes
   */
  public function newAttributes ()
  {
    return new FlowGridAttributes($this);
  }

  protected function setup ()
  {
    if (isset($this->attrs ()->data)) {
      $template = property ($this->attrsObj, 'repeat');
      if (isset($template)) {
        $this->defaultDataSource = $this->attrs ()->data;
        $dataIter                = $this->defaultDataSource->getIterator ();
        $dataIter->rewind ();
        if ($dataIter->valid ()) {
          do {
            $cloned = clone $template;
            $this->addChild ($cloned);
            $cloned->setup ();
            $dataIter->next ();
          } while ($dataIter->valid ());
        }
      }
    }
    $template = property ($this->attrsObj, 'new_element');
    if (isset($template)) {
      $this->addChild ($template);
      $template->setup ();
    }
  }

  protected function render ()
  {
    if (!empty($this->children)) {
      $this->cols = /*$this->style()->columns? $this->style()->columns :*/
        9999;
      $this->renderChildren ();
      if ($this->cellNum >= 0) {
        $this->endTag (); //tr
        if ($this->attrs ()->table_tag)
          $this->endTag (); //table
      }
    }
  }

  /**
   * @see Component::renderParameter()
   * @param Parameter $param
   */
  public function renderParameter (Parameter $param)
  {
    if (++$this->cellNum == 0 && $this->attrs ()->table_tag)
      $this->beginTag ('table', [
        //'align'	=> $this->style()->align,
        'class' => $this->attrs ()->class
      ]);
    if ($this->cellNum % $this->cols == 0) {
      ++$this->rowNum;
      if ($this->cellNum > 0)
        $this->endTag (); //tr
      $this->beginTag ('tr');
      $this->beginTag ('td', [
        'class' => $this->cellNum > 0 ? 'l' : 't l'
      ]);
    } else $this->beginTag ('td', $this->rowNum > 0
      ? null
      : [
        'class' => 't'
      ]);
    $this->beginContent ();
    $param->renderChildren ();
    $this->endTag (); //td
  }
}

