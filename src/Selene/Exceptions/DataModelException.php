<?php
namespace Selene\Exceptions;

use Selene\DataObject;

class DataModelException extends BaseException {

  public function __construct(DataObject $obj, $msg) {
    $dump = print_r($obj, TRUE);
    parent::__construct("$msg<h4>Instance properties:</h4><blockquote><pre>$dump</pre></blockquote>", Status::FATAL,'Data model error');
  }

}
