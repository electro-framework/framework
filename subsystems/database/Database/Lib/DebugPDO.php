<?php
namespace Selenia\Database;

use PDOException;
use PhpKit\ExtPDO;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Selenia\Traits\DecoratorTrait;

class DebugPDO extends ExtPDO
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
    return $this->logQuery ($query, $params, [$this, 'parent::exec']);
  }

  public function query ($query, $params = null)
  {
    return $this->logQuery ($query, $params, [$this, 'parent::query']);
  }

  private function highlightQuery ($msg, array $keywords, $baseStyle)
  {
    $msg = preg_replace ("#`[^`]*`#", '<span class=dbcolumn>$0</span>', $msg);
    $msg = DebugConsole::highlight ($msg, $keywords, $baseStyle);
    return "<#i>$msg</#i>";
  }

  private function logQuery ($query, array $params = null, callable $execute)
  {
    0/0;
    $showQuery = function ($dur = null) use ($query, $params) {
      $query = trim ($query);
      DebugConsole::logger ('database')
                  ->inspect ('<#section|SQL QUERY>', $this->highlightQuery ($query, self::$SQL_KEYWORDS, 'identifier'));
      if (!empty($params))
        DebugConsole::logger ('database')->inspect ("<#header>Parameters</#header>", $params);
      if (isset($dur))
        DebugConsole::logger ('database')->inspect ("<#footer>Query took <b>$dur</b> seconds.</#footer>");
      DebugConsole::logger ('database')->inspect ('</#section>');
    };

    $start = microtime (true);
    try {
      $st = $execute ($query, $params);
    }
    catch (PDOException $e) {
      $showQuery ();
      DebugConsole::logger ('database')->inspect ('<#footer><#alert>Query failed!</#alert></#footer>');
      DebugConsole::throwErrorWithLog ($e);
    }
    $end = microtime (true);
    $dur = round ($end - $start, 4);
    $showQuery ($dur);
    /** @noinspection PhpUndefinedVariableInspection */
    return $st;
  }


}
