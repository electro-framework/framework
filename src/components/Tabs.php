<?php

use selene\matisse\AttributeType;
use selene\matisse\ComponentAttributes;
use selene\matisse\DataSet;
use selene\matisse\exceptions\ComponentException;
use selene\matisse\VisualComponent;

class TabsAttributes extends ComponentAttributes
{
  public $selected_index      = 0; //-1 to not preselect any tab
  public $value;
  public $disabled            = false;
  public $pages;
  public $page_template;
  public $data;
  public $value_field         = 'value';
  public $label_field         = 'label';
  public $lazy_creation       = false;
  public $tab_align           = 'left';
  public $container_css_class = '';

  protected function typeof_selected_index () { return AttributeType::NUM; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_pages () { return AttributeType::SRC; }
  protected function typeof_page_template () { return AttributeType::SRC; }
  protected function typeof_data () { return AttributeType::DATA; }
  protected function typeof_value_field () { return AttributeType::TEXT; }
  protected function typeof_label_field () { return AttributeType::TEXT; }
  protected function typeof_lazy_creation () { return AttributeType::BOOL; }
  protected function typeof_tab_align () { return AttributeType::TEXT; }
  protected function typeof_container_css_class () { return AttributeType::TEXT; }
}

class TabsData
{
  public $id;
  public $value;
  public $label;
  public $url;
  public $icon;
  public $inactive;
  public $disabled;
}

class Tabs extends VisualComponent
{

  protected $autoId = true;

  /**
   * Indicates if the component contains tab-pages.
   * @var boolean
   */
  protected $hasPages;

  private $count = 0;
  private $selIdx;

  /**
   * Returns the component's attributes.
   * @return TabsAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return TabsAttributes
   */
  public function newAttributes ()
  {
    return new TabsAttributes($this);
  }

  protected function render ()
  {
    $this->selIdx = $this->attrs ()->selected_index;
    convertToInt ($selIdx);
    $pages = $this->getChildren ('pages');
    if (!empty($pages)) {
      //create data source for tabs from tab-pages defined on the source markup
      $data = [];
      foreach ($pages as $idx => $tabPage) {
        $t           = new TabsData();
        $t->id       = $tabPage->attrs ()->id;
        $t->value    = either ($tabPage->attrs ()->value, $idx);
        $t->label    = $tabPage->attrs ()->label;
        $t->icon     = $tabPage->attrs ()->icon;
        $t->inactive = $tabPage->inactive;
        $t->disabled = $tabPage->attrs ()->disabled;
        $t->url      = $tabPage->attrs ()->url;
        $data[]      = $t;
      }
      $data                = new DataSet($data);
      $propagateDataSource = false;
    } else {
      $data                = $this->attrs ()->data;
      $propagateDataSource = true;
    }
    if (!empty($data)) {
      $template = $this->attrs ()->page_template;
      if (isset($template)) {
        if (isset($pages))
          throw new ComponentException($this,
            "You may not define both the <b>p:page-template</b> and the <b>p:pages</p> parameters.");
        $this->hasPages = true;
      }
      if ($propagateDataSource)
        $this->defaultDataSource = $data;
      $value = either ($this->attrs ()->value, $this->selIdx);
      foreach ($data as $idx => $record) {
        if (!get ($record, 'inactive')) {
          $isSel = get ($record, $this->attrs ()->value_field) === $value;
          if ($isSel)
            $this->selIdx = $this->count;
          ++$this->count;
          //create tab
          $tab               = new Tab($this->context, [
            'id'       => $this->attrs ()->id . 'Tab' . $idx,
            'name'     => $this->attrs ()->id,
            'value'    => get ($record, $this->attrs ()->value_field),
            'label'    => get ($record, $this->attrs ()->label_field),
            'url'      => get ($record, 'url'),
            //'class'         => $this->style()->tab_class,
            //'css_class'     => $this->style()->tab_css_class,
            'disabled' => get ($record, 'disabled') || $this->attrs ()->disabled,
            'selected' => false//$isSel
          ], [
            //'width'         => $this->style()->tab_width,
            //'height'        => $this->style()->tab_height,
            'icon' => get ($record, 'icon'),
            //'icon_align'    => $this->style()->tab_icon_align
          ]);
          $tab->container_id = $this->attrs ()->id;
          $this->addChild ($tab);
          //create tab-page
          $newTemplate = isset($template) ? clone $template : null;
          if (isset($template)) {
            $page = new TabPage($this->context, [
              'id'            => get ($record, 'id', $this->attrs ()->id . 'Page' . $idx),
              'label'         => get ($record, $this->attrs ()->label_field),
              'icon'          => get ($record, 'icon'),
              'content'       => $newTemplate,
              'lazy_creation' => $this->attrs ()->lazy_creation
            ]);
            $newTemplate->attachTo ($page);
            $this->addChild ($page);
          }
        }
      }
      if (!empty($pages)) {
        $this->addChildren ($pages);
        if ($this->selIdx >= 0)
          $pages[$this->selIdx]->attrs ()->selected = true;
        $this->setupSet ($pages);
        $this->hasPages = true;
      }
    }

    //--------------------------------

    $this->beginTag ('fieldset', [
      'class' => enum (' ', 'tabGroup', concat ('align_', $this->attrs ()->tab_align))
    ]);
    $this->beginContent ();
    $p = 0;
    if ($this->attrs ()->tab_align == 'right') {
      $selIdx = $this->count - $this->selIdx - 1;
      for ($i = count ($this->children) - 1; $i >= 0; --$i) {
        $child = $this->children[$i];
        if ($child->className == 'Tab') {
          $s                         = $selIdx == $p++;
          $child->attrs ()->selected = $s;
          if ($s) $selName = $child->attrs ()->id;
          $child->doRender ();
        }
      }
    } else {
      $selIdx = $this->selIdx;
      foreach ($this->children as $child)
        if ($child->className == 'Tab') {
          $s                         = $selIdx == $p++;
          $child->attrs ()->selected = $s;
          if ($s) $selName = $child->attrs ()->id;
          $child->doRender ();
        }
    }
    $this->endTag ();

    if ($this->hasPages) {
      $this->beginTag ('div', [
        'id'    => $this->attrs ()->id . 'Pages',
        'class' => enum (' ', 'TabsContainer', $this->attrs ()->container_css_class)
      ]);
      $this->beginContent ();
      $p      = 0;
      $selIdx = $this->selIdx;
      foreach ($this->children as $child)
        if ($child->className == 'TabPage') {
          $s                         = $selIdx == $p++;
          $child->attrs ()->selected = $s;
          if ($s) $sel = $child;
          $child->doRender ();
        }
      $this->endTag ();
      if (isset($sel))
        $this->addTag ('script', null, "Tab_change(\$f('{$selName}Field'),'{$this->attrs()->id}')");
    }
  }

}

