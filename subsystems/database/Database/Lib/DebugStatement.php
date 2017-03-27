<?php

namespace Electro\Database\Lib;

use PDO;
use PDOException;
use PhpKit\WebConsole\DebugConsole\DebugConsole;

/**
 * Note: this class implements a decorator pattern but it still extends `PDOStatement` to be compatible with
 * functions that receive a type-hinted `PDOStatement` argument.
 *
 * <p>Note: rows are counted as they are fetched from the statement object; the total count is displayed either when
 * a new query is executed or by an external call to `endLog` when the application logic has finished executing.
 *
 * <p>Warning: when two or more statements are being read simultaneously, the row count will not be correct.
 */
class DebugStatement extends \PDOStatement
{
  /** @var static|null */
  private static $currentStatement = null;
  /** @var string The SQL command, always in lower case (ex: 'select') */
  protected $command;
  /** @var \PDOStatement */
  protected $decorated;
  protected $doProfiling = true;
  protected $fetchCount  = 0;
  /** @var bool */
  protected $isSelect;
  /** @var array */
  protected $params = [];
  /** @var string The full SQL query */
  protected $query;

  public function __construct (\PDOStatement $statement, $query)
  {
    $this->decorated = $statement;
    $this->query     = trim ($query);
    $this->command   = strtolower (str_extractSegment ($this->query, '/\s/')[0]);
    $this->isSelect  = $this->command == 'select';
  }

  static function endLog ()
  {
    $st = static::$currentStatement;
    if ($st) {
      static::$currentStatement = null;
      $count                    = $st->isSelect ? $st->fetchCount : $st->rowCount ();
      DebugConsole::logger ('database')
                  ->write (sprintf ('; <b>%d</b> %s %s</#footer></#section>',
                    $count,
                    $count == 1 ? 'record' : 'records',
                    $st->isSelect ? 'fetched' : 'affected'
                  ));
    }
  }

  function __debugInfo ()
  {
    return [
      'decorated'  => $this->decorated,
      'query'      => $this->query,
      'params'     => $this->params,
      'fetchCount' => $this->fetchCount,
    ];
  }

  public function bindColumn ($column, &$param, $type = null, $maxlen = null, $driverdata = null)
  {
    return $this->decorated->bindColumn ($column, $param, $type, $maxlen, $driverdata);
  }

  public function bindParam ($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = null,
                             $driver_options = null)
  {
    return $this->decorated->bindParam ($parameter, $variable, $data_type, $length, $driver_options);
  }

  public function bindValue ($parameter, $value, $data_type = PDO::PARAM_STR)
  {
    $this->params[$parameter] = $value;
    return $this->decorated->bindValue ($parameter, $value, $data_type);
  }

  public function closeCursor ()
  {
    $this->doProfiling = true;
    $this->fetchCount  = 0;
    $this->params      = [];
    return $this->decorated->closeCursor ();
  }

  public function columnCount ()
  {
    return $this->decorated->columnCount ();
  }

  public function debugDumpParams ()
  {
    return $this->decorated->debugDumpParams ();
  }

  public function errorCode ()
  {
    return $this->decorated->errorCode ();
  }

  public function errorInfo ()
  {
    return $this->decorated->errorInfo ();
  }

  public function execute ($params = null)
  {
    if ($params)
      $this->params = $params;
    $this->logQuery ();
    return $this->profile (function () use ($params) {
      return $this->decorated->execute ($params);
    });
  }

  public function fetch ($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
  {
    $r = $this->decorated->fetch ($fetch_style, $cursor_orientation, $cursor_offset);
    if ($r !== false)
      ++$this->fetchCount;
    return $r;
  }

  public function fetchAll ($fetch_style = null, $fetch_argument = null, $ctor_args = null)
  {
    $count = func_num_args ();
    switch ($count) {
      case 0:
        $r = $this->decorated->fetchAll ();
        break;
      case 1:
        $r = $this->decorated->fetchAll ($fetch_style);
        break;
      case 2:
        $r = $this->decorated->fetchAll ($fetch_style, $fetch_argument);
        break;
      default:
        $r = $this->decorated->fetchAll ($fetch_style, $fetch_argument, $ctor_args);
    }
    if ($r !== false)
      $this->fetchCount += count ($r);
    return $r;
  }

  public function fetchColumn ($column_number = 0)
  {
    $r = $this->decorated->fetchColumn ($column_number);
    if ($r !== false)
      ++$this->fetchCount;
    return $r;
  }

  public function fetchObject ($class_name = "stdClass", $ctor_args = null)
  {
    $r = $this->decorated->fetchObject ($class_name, $ctor_args);
    if ($r !== false)
      ++$this->fetchCount;
    return $r;
  }

  public function getAttribute ($attribute)
  {
    return $this->decorated->getAttribute ($attribute);
  }

  public function getColumnMeta ($column)
  {
    return $this->decorated->getColumnMeta ($column);
  }

  public function nextRowset ()
  {
    $this->doProfiling = true;
    $this->fetchCount  = 0;
    return $this->decorated->nextRowset ();
  }

  public function rowCount ()
  {
    return $this->decorated->rowCount ();
  }

  public function setAttribute ($attribute, $value)
  {
    return $this->decorated->setAttribute ($attribute, $value);
  }

  public function setFetchMode ($mode, $params = null)
  {
    return $this->decorated->setFetchMode ($mode);
  }

  private function logDuration ($dur)
  {
    DebugConsole::logger ('database')
                ->write (sprintf ('<#footer>Query took <b>%s</b> milliseconds', $dur * 1000));
  }

  private function logQuery ()
  {
    static::endLog (); // close the previous panel, if one is open.
    static::$currentStatement = $this;
    DebugConsole::logger ('database')
                ->inspect ("<#section|SQL " . ($this->isSelect ? 'QUERY' : 'STATEMENT') . ">",
                  SqlFormatter::highlightQuery ($this->query));
    if ($this->params)
      DebugConsole::logger ('database')->write ("<#header>Parameters</#header>")->inspect ($this->params);
  }

  private function profile (callable $action)
  {
    if ($this->doProfiling) {
      $start = microtime (true);
      try {
        $r = $action ();
      }
      catch (PDOException $e) {
        DebugConsole::logger ('database')->write ('<#footer><#alert>Query failed!</#alert></#footer>');
        DebugConsole::throwErrorWithLog ($e);
      }
      $end = microtime (true);
      $dur = round ($end - $start, 4);
      $this->logDuration ($dur);
      $this->doProfiling = false;
      if (!$this->isSelect)
        static::endLog ();
      /** @noinspection PhpUndefinedVariableInspection */
      return $r;
    }
    return $action ();
  }

}
