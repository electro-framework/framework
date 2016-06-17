<?php
namespace Electro\Database\Lib;

use PDOException;
use PhpKit\ExtPDO;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Electro\Traits\DecoratorTrait;

class DebugPDO
{
  use DecoratorTrait;

  static private $SQL_KEYWORDS = [
    'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'JOIN', 'LEFT', 'RIGHT', 'OUTER',
    'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'UNION',
  ];

  public function __construct (ExtPDO $pdo)
  {
    $this->decorated = $pdo;
  }

  public function exec ($query, $params = null)
  {
    return $this->logQuery ('exec', $query, $params, false);
  }

  public function query ($query, $params = null)
  {
    return $this->logQuery ('query', $query, $params, true);
  }

  private function highlightQuery ($msg, array $keywords, $baseStyle)
  {
    $msg = preg_replace ("#`[^`]*`#", '<span class=dbcolumn>$0</span>', $msg);
    $msg = DebugConsole::highlight ($msg, $keywords, $baseStyle);
    return "<#i>$msg</#i>";
  }

  private function logQuery ($method, $query, array $params = null, $isSelect)
  {
    /** @var \PDOStatement $st */
    $st        = null;
    $showQuery = function ($dur = null) use ($query, $params, &$st, $isSelect) {
      $query = trim ($query);
      DebugConsole::logger ('database')
                  ->inspect ('<#section|SQL QUERY>', $this->highlightQuery ($query, self::$SQL_KEYWORDS, 'identifier'));
      if (!empty($params))
        DebugConsole::logger ('database')->write ("<#header>Parameters</#header>")->inspect ($params);
      DebugConsole::logger ('database')
                  ->write (sprintf ("<#footer>Query took <b>%s</b> milliseconds" .
                                    ($isSelect ? '' : ' and affected <b>%d</b> records') . "</#footer>",
                    $dur * 1000, $st ? $st->rowCount () : 0));
      DebugConsole::logger ('database')->write ('</#section>');
    };

    $start = microtime (true);
    try {
      $st = $this->decorated->$method ($query, $params);
    }
    catch (PDOException $e) {
      $showQuery ();
      DebugConsole::logger ('database')->write ('<#footer><#alert>Query failed!</#alert></#footer>');
      DebugConsole::throwErrorWithLog ($e);
    }
    $end = microtime (true);
    $dur = round ($end - $start, 4);
    $showQuery ($dur);
    return $st;
  }


}
