<?php
class SiteGroup extends SiteElement {

  public $defaultURI;
  public $baseSubnavURI;
  public $includeMainItemOnNav = false;

  public function getTypes() {
    return array_merge(parent::getTypes(),array(
      'defaultURI'           => 'string',
      'baseSubnavURI'        => 'string',
      'includeMainItemOnNav' => 'boolean'
    ));
  }

  public function __construct(array &$init = null) {
    parent::__construct($init);
  }

  public function preinit() {
    parent::preinit();
    if (isset($this->baseSubnavURI))
      $this->baseSubnavURI = str_replace('*','[^/]*',$this->baseSubnavURI);
  }

  public function searchFor($URI,$options = 0) {
    if ($this->matchesURI($URI)) {
      $this->selected = true;
      if ($options & SiteMapSearchOptions::INCLUDE_GROUPS)
        return $this;
    }
    if (isset($this->pages))
      foreach ($this->pages as $page) {
        $result = $page->searchFor($URI,$key = null);
        if (isset($result)) {
          $this->selected = true;
          return $result;
        }
      }
    return null;
  }
}
