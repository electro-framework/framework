<?php

namespace Electro\Database\Lib;

use PhpKit\WebConsole\DebugConsole\DebugConsole;

class SqlFormatter
{
  const SQL_KEYWORDS = [
    'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'JOIN', 'LEFT', 'RIGHT', 'OUTER',
    'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'UNION',
  ];

  static function highlightQuery ($msg, array $keywords = self::SQL_KEYWORDS, $baseStyle = 'identifier')
  {
    $msg = preg_replace ("#`[^`]*`#", '<span class=dbcolumn>$0</span>', $msg);
    $msg = DebugConsole::highlight ($msg, $keywords, $baseStyle);
    return "<#i>$msg</#i>";
  }

}
