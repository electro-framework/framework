<?php
/**
 * Defines a website location which represents an operation on the same data as
 * its parent page (ex. a detail page). It has the same basic features of its
 * parent, but has a different URI and module code.
 */
class SiteSubpage extends SitePage {

  public function init($parent) {
    $fields = $this->getTypes();
    foreach ($fields as $field=>$type) {
      if (!isset($this->$field) && isset($parent->$field) && !is_array($parent->$field))
        $this->$field = $parent->$field;
    }
    $this->onMenu = false;
    parent::init($parent);
  }

}
