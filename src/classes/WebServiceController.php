<?php
class WebServiceController extends Controller {

  public $isWebService = true;

  //--------------------------------------------------------------------------
  protected function initialize()
  //--------------------------------------------------------------------------
  {
    parent::initialize();
    $this->page->contentIsXML = true;
  }

}
