<?php

namespace Electro\Database\Lib;

use PDO;
use PDOException;
use PhpKit\ExtPDO\ExtPDO;
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
  /** @var string The SQL command, always in lower case (ex: 'select') */
  protected $command;
  /** @var \PDOStatement */
  protected $decorated;
  /** @var bool */
  protected $isSelect;
  /** @var array */
  protected $params = [];
  /** @var string The full SQL query */
  protected $query;
  /** @var int Either the number of rows returned from a SELECT query or how many rows were affected by a INSERT/UPDATE/DELETE query */
  protected $rowCount = 0;
  /** @var ExtPDO */
  private $pdo;

  public function __construct (\PDOStatement $statement, $query, $pdo)
  {
    $this->decorated = $statement;
    $this->query     = trim ($query);
    $this->command   = strtolower (str_extractSegment ($this->query, '/\s/')[0]);
    $this->isSelect  = $this->command == 'select';
    $this->pdo       = $pdo;
  }

  function __debugInfo ()
  {
    return [
      'decorated'  => $this->decorated,
      'query'      => $this->query,
      'params'     => $this->params,
      'fetchCount' => $this->rowCount,
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
    $this->params[is_numeric ($parameter) ? $parameter - 1 : $parameter] = $value;
    return $this->decorated->bindValue ($parameter, $value, $data_type);
  }

  public function closeCursor ()
  {
    $this->params = [];
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
    if (isset($params))
      $this->params = $params;
    $this->logQuery ();
    $this->countRows ();
    $r = $this->profile (function () use ($params) {
      return $this->decorated->execute ($params);
    });
    $this->endLog ();
    return $r;
  }

  public function fetch ($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
  {
    return $this->decorated->fetch ($fetch_style, $cursor_orientation, $cursor_offset);
  }

  public function fetchAll ($fetch_style = null, $fetch_argument = null, $ctor_args = null)
  {
    $count = func_num_args ();
    switch ($count) {
      case 0:
        return $this->decorated->fetchAll ();
        break;
      case 1:
        return $this->decorated->fetchAll ($fetch_style);
        break;
      case 2:
        return $this->decorated->fetchAll ($fetch_style, $fetch_argument);
        break;
      default:
        return $this->decorated->fetchAll ($fetch_style, $fetch_argument, $ctor_args);
    }
  }

  public function fetchColumn ($column_number = 0)
  {
    return $this->decorated->fetchColumn ($column_number);
  }

  public function fetchObject ($class_name = "stdClass", $ctor_args = null)
  {
    return $this->decorated->fetchObject ($class_name, $ctor_args);
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

  protected function countRows ()
  {
    if (!$this->isSelect) {
      $this->rowCount = $this->rowCount ();
      return;
    }
    $query = sprintf ('SELECT COUNT(*) FROM (%s) AS __countQuery', $this->query);
    try {
      $this->rowCount = null;
      $st             = $this->pdo->select ($query, $this->params);
      if (!$st)
        return;
      $this->rowCount = $st->fetchColumn (0);
      $st->closeCursor ();
    }
    catch (PDOException $e) {
      DebugConsole::logger ('database')
                  ->log ('notice',
                    sprintf ("<p>While inspecting a database query, an error occurred computing the size of its result set:</p>%s<p><br>Failed inspection query:</p>%s",
                      $e->getMessage (), SqlFormatter::highlightQuery ($query)));
    }
  }

  protected function endLog ()
  {
    $log   = DebugConsole::logger ('database');
    $count = $this->rowCount;
    if (is_null ($count)) {
      $log->write ('; unknown result set size');
    }
    else
      $log->write (sprintf ('; <b>%d</b> %s %s',
        $count,
        $count == 1 ? 'record' : 'records',
        $this->isSelect ? 'returned' : 'affected'
      ));
    $log->write ('</#footer></#section>');
  }

  protected function logDuration ($dur)
  {
    DebugConsole::logger ('database')
                ->write (sprintf ('<#footer>Query took <b>%s</b> milliseconds', $dur * 1000));
  }

  protected function logQuery ()
  {
    DebugConsole::logger ('database')
                ->inspect ("<#section|SQL " . ($this->isSelect ? 'QUERY' : 'STATEMENT') . ">",
                  SqlFormatter::highlightQuery ($this->query));
    if ($this->params)
      DebugConsole::logger ('database')->write ("<#header>Parameters</#header>")->inspect ($this->params);
  }

  protected function profile (callable $action)
  {
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
    /** @noinspection PhpUndefinedVariableInspection */
    return $r;
  }
}
