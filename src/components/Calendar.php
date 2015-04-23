<?php
use selene\matisse\AttributeType;
use selene\matisse\Component;
use selene\matisse\ComponentAttributes;
use selene\matisse\exceptions\ComponentException;
use selene\matisse\IAttributes;

class CalendarAttributes extends ComponentAttributes {
    public $name;
    public $value                 = '';
    public $year;
    public $month                 = 1;
    public $months                = 1;
    public $start_date;
    public $selectable            = true;
    public $multiple_selection    = false;
    public $single_range          = true;
    public $auto_select           = true;
    public $disabled              = false;
    public $month_navigation      = false;
    public $on_render_day;
    public $on_select;            //function name (params: date,event)
    public $on_change;            //javascript
    public $language              = null; //ex: en-US, if not specified Controller->$langISO or Controller->$lang are used
    public $date_format           = 'yyyy-MM-dd';
    public $preselected_days      = ''; //comma delimited list of dates, ex: 2010-01-1,2010-01-12,2010-07-31
    public $marked_days           = ''; //comma delimited list of 'date':code elements, ex: '2010-01-02':1,'2010-02-27':'holiday'
    public $marked_days_2         = ''; //comma delimited list of 'date':code elements, ex: '2010-01-02':1,'2010-02-27':'holiday'

    protected function typeof_name                () { return AttributeType::ID; }
    protected function typeof_year                () { return AttributeType::NUM; }
    protected function typeof_month               () { return AttributeType::NUM; }
    protected function typeof_months              () { return AttributeType::NUM; }
    protected function typeof_start_date          () { return AttributeType::TEXT; }
    protected function typeof_selectable          () { return AttributeType::BOOL; }
    protected function typeof_multiple_selection  () { return AttributeType::BOOL; }
    protected function typeof_single_range        () { return AttributeType::BOOL; }
    protected function typeof_auto_select         () { return AttributeType::BOOL; }
    protected function typeof_disabled            () { return AttributeType::BOOL; }
    protected function typeof_month_navigation    () { return AttributeType::BOOL; }
    protected function typeof_on_render_day       () { return AttributeType::TEXT; }
    protected function typeof_on_select           () { return AttributeType::TEXT; }
    protected function typeof_on_change           () { return AttributeType::TEXT; }
    protected function typeof_language            () { return AttributeType::TEXT; }
    protected function typeof_date_format         () { return AttributeType::TEXT; }
    protected function typeof_preselected_days    () { return AttributeType::TEXT; }
    protected function typeof_marked_days         () { return AttributeType::TEXT; }
    protected function typeof_marked_days_2       () { return AttributeType::TEXT; }
}

class Calendar extends Component implements IAttributes
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return CalendarAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return CalendarAttributes
   */
  public function newAttributes ()
  {
    return new CalendarAttributes($this);
  }

  protected function setup ()
  {
    global $application, $controller;
    if (!isset($this->attrs ()->name))
      $this->attrs ()->name = $this->attrs ()->id;
    $lang = property ($this->attrs (), 'language',
      property ($controller, 'langISO', $controller->lang));
    $suf = $lang ? "-$lang" : '';
    $js = "$application->addonsPath//components/calendar/DateJS/date$suf.js";
    if (!file_exists ($application->toFilePath ($js)))
      throw new ComponentException($this, "Invalid value for the <b>language</b> attribute.");
    $this->page->addScript ($js);
    $this->page->addScript ("$application->addonsPath/components/calendar/calendar.js");
  }

  protected function render ()
  {
    $attr = $this->attrs ();
    $this->beginTag ('div');
    $this->addAttribute ('id', $this->attrs ()->id);
    $this->addAttribute ('class', enum (' ',
      $this->className,
      $this->attrs ()->class,
      $this->attrs ()->css_class
    ));
    $this->endTag ();
    $this->addTag ('input', ['type' => 'hidden', 'name' => $attr->name, 'value' => $attr->value]);
    $date = new DateTime();
    $y = property ($attr, "year", $date->format ('Y'));
    $m = property ($attr, "month", intval ($date->format ('m'), 10));
    $selectable = boolToStr ($attr->selectable);
    $multiple_selection = boolToStr ($attr->multiple_selection);
    $auto_select = boolToStr ($attr->auto_select);
    $disabled = boolToStr ($attr->disabled);
    $single_range = boolToStr ($attr->single_range);
    $monthNav = boolToStr ($attr->month_navigation);
    $this->addTag ('script', null, "drawCalendar({" .
      "id:'$attr->id'," .
      "name:'$attr->name'," .
      "year:$y," .
      "month:$m," .
      "months:$attr->months," .
      (isset($attr->start_date) ? "startDate:'$attr->start_date'," : '') .
      "selectable:$selectable," .
      "multipleSelection:$multiple_selection," .
      "singleRange:$single_range," .
      "autoSelect:$auto_select," .
      "disabled:$disabled," .
      "monthNavigation:$monthNav," .
      "dateFormat:'$attr->date_format'," .
      "preselectedDays:[$attr->preselected_days]," .
      "markedDays:{{
    $attr->marked_days}},".
        "markedDays2:{{
    $attr->marked_days_2}},".
        "onRenderDay:'$attr->on_render_day'," .
        "onSelect:'$attr->on_select'," .
        "onChange:'$attr->on_change'})");
    }
}

