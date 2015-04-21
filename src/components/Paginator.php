<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class PaginatorAttributes extends ComponentAttributes
{

  public $page;
  public $total;
  public $uri;
  public $page_count;

  protected function typeof_page () { return AttributeType::NUM; }
  protected function typeof_total () { return AttributeType::NUM; }
  protected function typeof_uri () { return AttributeType::TEXT; }
  protected function typeof_page_count () { return AttributeType::NUM; }
}

class Paginator extends VisualComponent
{

  /**
   * Returns the component's attributes.
   * @return PaginatorAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return PaginatorAttributes
   */
  public function newAttributes ()
  {
    return new PaginatorAttributes($this);
  }

  protected function preRender ()
  {
    if ($this->attrs ()->total > 1)
      parent::preRender ();
  }

  protected function postRender ()
  {
    if ($this->attrs ()->total > 1)
      parent::postRender ();
  }

  protected function render ()
  {
    $SIZE  = floor (($this->attrs ()->page_count - 1) / 2);
    $page  = $this->attrs ()->page;
    $total = $this->attrs ()->total;
    if ($total < 2) return;
    $uri   = $this->attrs ()->uri;
    $start = $page - $SIZE;
    $end   = $start + 2 * $SIZE;
    if ($start < 1) {
      $d = -$start + 1;
      $start += $d;
      $end += $d;
    }
    if ($end > $total) {
      $d = $end - $total;
      $end -= $d;
      $start -= $d;
      if ($start < 1) $start = 1;
    }
    $this->beginTag ('div');
    if ($start > 1) {
      $st = $start - 1;
      if ($st < 1) $st = 1;
      $this->addTag ('a', ['href' => "$uri&p=$st", 'class' => 'prev']);
      $this->beginContent ();
      echo '<span>...</span>';
    }
    for ($n = $start; $n <= $end; ++$n)
      $this->addTag ('a', ['href' => "$uri&p=$n", 'class' => $n == $page ? 'selected' : ''], $n);
    if ($end < $total) {
      $this->beginContent ();
      echo '<span>...</span>';
      $ed = $end + 1;
      if ($ed > $total) $ed = $total;
      $this->addTag ('a', ['href' => "$uri&p=$ed", 'class' => 'next']);
    }
    $this->endTag ();
  }

}

