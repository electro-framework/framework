<?php
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\Components\Parameter;
use Selene\Matisse\VisualComponent;

class DataGridAttributes extends ComponentAttributes
{

  public $column;
  public $row_template;
  public $no_data;
  public $data;
  public $paging_type = 'simple_numbers';
  public $ajax        = false;
  public $action;
  public $detail_url;
  public $clickable   = false;

  /*
   * Attributes for each column:
   * - type="row-selector|action|input". Note: if set, clicks on the column have no effect.
   * - align="left|center|right"
   * - title="t" (t is text)
   * - width="n|n%" (n is a number)
   */
  protected function typeof_column () { return AttributeType::PARAMS; }
  protected function typeof_row_template () { return AttributeType::SRC; }
  protected function typeof_no_data () { return AttributeType::SRC; }
  protected function typeof_data () { return AttributeType::DATA; }
  protected function typeof_paging_type () { return AttributeType::TEXT; }
  protected function enum_paging_type () { return ['simple', 'simple_numbers', 'full', 'full_numbers']; }
  protected function typeof_ajax () { return AttributeType::BOOL; }
  protected function typeof_action () { return AttributeType::TEXT; }
  protected function typeof_detail_url () { return AttributeType::TEXT; }
  protected function typeof_clickable () { return AttributeType::BOOL; }

}

class DataGrid extends VisualComponent
{
  const PUBLIC_URI = 'modules/admin';

  protected static $MIN_PAGE_ITEMS = [
    'simple'         => 0, // n/a
    'full'           => 0, // n/a
    'simple_numbers' => 3,
    'full_numbers'   => 5
  ];

  public    $cssClassName = 'box';
  protected $autoId       = true;

  private $enableRowClick = false;
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
    $attr = $this->attrs ();
    $this->page->addInlineScript (<<<JAVASCRIPT
function check(ev,id,action) {
    action = action || 'check';
    ev.stopPropagation();
    $.post(location.href, { _action: action, id: id });
}
JAVASCRIPT
      , 'datagridInit');
    $id          = $attr->id;
    $minPagItems = self::$MIN_PAGE_ITEMS [$attr->paging_type];
    $PUBLIC_URI = self::PUBLIC_URI;
    $language    = $controller->lang != 'en'
      ? "language:     { url: '$PUBLIC_URI/js/datatables/{$controller->langISO}.json' }," : '';

    $this->setupColumns ($attr->column);
    $rowTemplate = $attr->row_template;
    if (isset($rowTemplate)) {
      $this->enableRowClick    = $rowTemplate->isAttributeSet ('on_click')
                                 || $rowTemplate->isAttributeSet ('on_click_script');
      $this->defaultDataSource = $attr->data;
    }

    // AJAX MODE

    if ($attr->ajax) {
      $url                  = $_SERVER['REQUEST_URI'];
      $action               = $attr->action;
      $detailUrl            = $attr->detail_url;
      $this->enableRowClick = $attr->clickable;
      $this->page->addInlineDeferredScript (<<<JavaScript
$('#$id table').dataTable({
  serverSide:   true,
  paging:       true,
  lengthChange: true,
  searching:    true,
  ordering:     true,
  info:         true,
  autoWidth:    false,
  responsive:   true,
  pageLength:   mem.get ('prefs.rowsPerPage', 10),
  lengthMenu:   [10, 15, 20, 50, 100],
  $language
  ajax: {
     url: '$url',
     type: 'POST',
     data: {
        _action: '$action'
    }
   },
  initComplete: function() {
    $('#$id').show();
  },
  drawCallback: function() {
    $('#$id [data-nck]').on('click', function(ev) { ev.stopPropagation() });
  }
}).on ('length.dt', function (e,cfg,len) {
  mem.set ('prefs.rowsPerPage', len);
}).on ('click', 'tbody tr', function () {
    location.href = '$detailUrl' + $(this).attr('rowid');
});
JavaScript
      );
    }
    else {

      // IMMEDIATE MODE

      $this->page->addInlineDeferredScript (<<<JavaScript
$('#$id table').dataTable({
  paging:       true,
  lengthChange: true,
  searching:    true,
  ordering:     true,
  info:         true,
  autoWidth:    false,
  responsive:   true,
  pageLength:   mem.get ('prefs.rowsPerPage', 10),
  lengthMenu:   [10, 15, 20, 50, 100],
  pagingType:   '{$attr->paging_type}',
  $language
  initComplete: function() {
    $('#$id').show();
  },
  drawCallback: function() {
    $('#$id [data-nck]').on('click', function(ev) { ev.stopPropagation() });
    var p = $('#$id .pagination');
    p.css ('display', p.children().length <= $minPagItems ? 'none' : 'block');
  }
}).on ('length.dt', function (e,cfg,len) {
  mem.set ('prefs.rowsPerPage', len);
});
JavaScript
      );
      if (isset($this->defaultDataSource)) {
        $dataIter = $this->defaultDataSource->getIterator ();
        $dataIter->rewind ();
        $valid = $dataIter->valid ();
      }
      else $valid = true;
      if ($valid) {
        $columnsCfg = $attr->column;
        $this->beginTag ('table', [
          'class' => enum (' ', 'table table-striped', $this->enableRowClick ? 'table-clickable' : '')
        ]);
        $this->beginContent ();
        $this->renderHeader ($columnsCfg);
        if (!$attr->ajax) {
          $idx = 0;
          do {
            $this->renderRow ($idx++, $rowTemplate->children, $columnsCfg, $rowTemplate);
            $dataIter->next ();
          } while ($dataIter->valid ());
        }
        $this->endTag ();
      }
      else $this->renderSet ($this->getChildren ('no_data'));
    }
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
        }
        else {
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
