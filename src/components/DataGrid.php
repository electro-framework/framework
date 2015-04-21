<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\components\Parameter;
use impactwave\matisse\VisualComponent;

class DataGridAttributes extends ComponentAttributes
{

  public $column;
  public $row_template;
  public $no_data;
  public $data;

  /*
   * Attributes for each column:
   * - type="row-selector|action|input". Note: if set, clicks on the column have no effect.
   * - align="left|center|right"
   * - title="t" (t is text)
   * - width="n|n%" (n is a number)
   */
  protected function typeof_column ()
  {
    return AttributeType::PARAMS;
  }

  protected function typeof_row_template ()
  {
    return AttributeType::SRC;
  }

  protected function typeof_no_data ()
  {
    return AttributeType::SRC;
  }

  protected function typeof_data ()
  {
    return AttributeType::DATA;
  }

}

class DataGrid extends VisualComponent
{

  public    $cssClassName   = 'box';
  protected $autoId         = true;
  private   $enableRowClick = false;

  /**
   * Returns the component's attributes.
   * @return DataGridAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return DataGridAttributes
   */
  public function newAttributes ()
  {
    return new DataGridAttributes($this);
  }

  protected function render ()
  {
    global $application, $controller;
    if (isset($this->attrs ()->data)) {
      $this->setupColumns ($this->attrs ()->column);
      $rowTemplate = $this->attrs ()->row_template;
      if (isset($rowTemplate)) {
        $this->enableRowClick    = $rowTemplate->isAttributeSet ('on_click')
                                   || $rowTemplate->isAttributeSet ('on_click_script');
        $this->defaultDataSource = $this->attrs ()->data;
      }
    } else return;
    $language = $controller->lang != 'en' ? "language:     { url: '$application->baseURI/js/datatables/{$controller->langISO}.json' }," : '';
    $this->page->addInlineDeferredScript (<<<JavaScript
    $('#{$this->attrs ()->id} table').dataTable({
      paging:       true,
      lengthChange: true,
      searching:    true,
      ordering:     true,
      info:         true,
      autoWidth:    false,
      responsive:   true,
      pageLength:   mem.get ('prefs.rowsPerPage', {$application->pageSize}),
      lengthMenu:   [10, 15, 20, 50, 100],
      $language
      initComplete: function() {
        $('#{$this->attrs ()->id}').show();
      },
      drawCallback: function() {
        $('#{$this->attrs ()->id} [data-nck]').on('click', function(ev) { ev.stopPropagation() });
      }
    }).on ('length.dt', function (e,cfg,len) {
      mem.set ('prefs.rowsPerPage', len);
    });
JavaScript
    );

    $this->beginTag ('div', ['class' => 'box-body']);

    $dataIter = $this->defaultDataSource->getIterator ();
    $dataIter->rewind ();
    if ($dataIter->valid ()) {
      $columnsCfg = $this->attrs ()->column;
      $this->beginTag ('table', [
        'class' => enum (' ', 'table table-striped', $this->enableRowClick ? 'table-clickable' : '')
      ]);
      $this->beginContent ();
      $this->renderHeader ($columnsCfg);
      $idx = 0;
      do {
        $this->renderRow ($idx++, $rowTemplate->children, $columnsCfg, $rowTemplate);
        $dataIter->next ();
      } while ($dataIter->valid ());
      $this->endTag ();
    } else {
      $this->beginContent ();
      $this->renderSet ($this->getChildren ('no_data'));
    }
    $this->endTag ();
  }

  private function renderRow ($idx, array $columns, array $columnsCfg, Parameter $row)
  {
    $row->databind ();
    $this->beginTag ('tr');
    $this->addAttribute ('class', 'R' . ($idx % 2));
    if ($this->enableRowClick) {
      $onclick = property ($row->attrs (), 'on_click');
      if (isset($onclick))
        $onclick = "go('$onclick',event)";
      else $onclick = property ($row->attrs (), 'on_click_script');
      $this->addAttribute ('onclick', $onclick);
    }
    foreach ($columns as $k => $col) {
      $colCfg = get ($columnsCfg, $k);
      if (isset($colCfg)) {
        $colAttrs = $colCfg->attrs ();
        $colType  = property ($colAttrs, 'type', '');
        $al       = property ($colAttrs, 'align');;
        $isText = empty($colType);
        $this->beginTag ($colType == 'row-selector' ? 'th' : 'td');
        //if (isset($al))
        $this->addAttribute ('class', "ta-$al");
        if ($isText) {
          $this->beginContent ();
          $col->renderChildren ();
        } else {
          if ($this->enableRowClick)
            $this->addAttribute ('data-nck');
          $this->beginContent ();
          $col->renderChildren ();
        }
        $this->endTag ();
      }
    }
    $this->endTag ();
  }

  private function setupColumns (array $columns)
  {
    $id     = $this->attrs ()->id;
    $styles = '';
    foreach ($columns as $k => $col) {
      $al = $col->attrs ()->align;
      if (isset($al))
        $styles .= "#$id .c$k{text-align:$al}";
      $al = $col->attrs ()->header_align;
      if (isset($al))
        $styles .= "#$id .h$k{text-align:$al}";
    }
    $this->page->addInlineCss ($styles);
  }

  private function renderHeader (array $columns)
  {
    $id = $this->attrs ()->id;
    foreach ($columns as $k => $col) {
      $w = $col->attrs ()->width;
      if (strpos ($w, '%') === false && $this->page->browserIsIE)
        $w -= 3;
      $this->addTag ('col', isset($w) ? ['width' => $w] : null);
    }
    $this->beginTag ('thead');
    foreach ($columns as $k => $col) {
      $al = $col->attrs ()->get ('header_align', $col->attrs ()->align);
      if (isset($al))
        $this->page->addInlineCss ("#$id .h$k{text-align:$al}");
      $this->beginTag ('th');
      $this->setContent ($col->attrs ()->title);
      $this->endTag ();
    }
    $this->endTag ();
  }

}
