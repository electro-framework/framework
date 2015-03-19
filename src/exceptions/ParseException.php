<?php
namespace impactwave\matisse\exceptions;

class ParseException extends MatisseException
{
  public function __construct ($msg, $body = null, $start = null, $end = null)
  {
    $b = $start > 100 ? $start - 100 : 0;
    $m = $msg;
    if (isset($body))
      $m .= "\n\nError location:\n\n...<span style='color:#C00'>" .
            htmlentities (substr ($body, $b, $start - $b), null, 'utf-8') .
            '<b>' . htmlentities (substr ($body, $start, $end - $start), null, 'utf-8') . '</b>' .
            htmlentities (substr ($body, $end, 100), null, 'utf-8') . '</span>...';
    parent::__construct ($m, 'Parse error');
  }

}
