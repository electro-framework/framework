<?php
namespace Selene\Matisse\Exceptions;

class ParseException extends MatisseException
{
  public function __construct ($msg, $body = null, $start = null, $end = null)
  {
    $b = $start > 100 ? $start - 100 : 0;
    $m = $msg;
    if (isset($body))
      $m .= "<h4>Error location:</h4><code>..." .
                   htmlentities (substr ($body, $b, $start - $b), null, 'utf-8') .
                   '<b>' . htmlentities (substr ($body, $start, $end - $start + 1), null, 'utf-8') . '</b>' .
                   htmlentities (substr ($body, $end + 1, 100), null, 'utf-8') . '...</code>';
    parent::__construct ($m, 'Parsing error');
  }

}
