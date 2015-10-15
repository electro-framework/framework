<?php
use PhpKit\WebConsole\WebConsole;

$transactionDepth = 0;

/**
 * @return PDO
 */
function db () {
  /** PDO */
  global $db;
  if (!isset($db)) database_open ();
  return $db;
}

//--------------------------------------------------------------------------
/** Do not call directly! */
function database_open ()
//--------------------------------------------------------------------------
{
  global $db, $application;

  $database = $_ENV['DB_DATABASE'];
  $options  = [];

  if ($_ENV['DB_DRIVER'] == 'sqlite') {
    if ($database != ':memory:') {
      if ($database[0] != '/')
        $database = "$application->baseDirectory/$database";
    }
    $dsn = "sqlite:$database";
  }
  else {

    $dsn = "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOST']};dbname=$database";
    if (!empty ($_ENV['DB_PORT']))
      $dsn .= ";port={$_ENV['DB_PORT']}";
    if (!empty ($_ENV['DB_UNIX_SOCKET']))
      $dsn .= ";unix_socket={$_ENV['DB_UNIX_SOCKET']}";

    // Options specific to the MySQL driver.
    if ($_ENV['DB_DRIVER'] == 'mysql') {
      if (!empty($_ENV['DB_CHARSET'])) {
        $cmd = "SET NAMES '{$_ENV['DB_CHARSET']}'";
        if (!empty($_ENV['DB_COLLATION'])) {
          $cmd .= " COLLATE '{$_ENV['DB_COLLATION']}'";
        }
        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => $cmd, PDO::MYSQL_ATTR_FOUND_ROWS => true];
      }
    }

  }
  $options [PDO::ATTR_ERRMODE]            = PDO::ERRMODE_EXCEPTION;
  $options [PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
  $options [PDO::ATTR_TIMEOUT]            = 5;
  $options [PDO::ATTR_EMULATE_PREPARES]   = false; // Required so that the mysqlnd driver returns the proper field data types.

  try {
    $db = new PDO ($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
    if ($_ENV['DB_DRIVER'] == 'sqlite')
      $db->exec ('PRAGMA foreign_keys = ON;');
  } catch (PDOException $e) {
    $e = new PDOException(
      $e->getMessage ()
      . "\n\nDatabase: <path>$database</path>" .
      ($application->debugMode ?
        "<p>Driver: {$_ENV['DB_DRIVER']}" .
        "<p>Host: {$_ENV['DB_HOST']}" .
        "<p>Username: {$_ENV['DB_USERNAME']}" .
        "<p>Password: {$_ENV['DB_PASSWORD']}" .
        "<p>Conn.str: $dsn"
        : ''),
      $e->getCode ());
    throw $e;
  }
}

function highlightQuery ($msg, array $keywords, $baseStyle)
{
  $msg = preg_replace ("#`[^`]*`#", '<span class=dbcolumn>$0</span>', $msg);
  $msg = WebConsole::highlight ($msg, $keywords, $baseStyle);
  return "<#i>$msg</#i>";
}

//--------------------------------------------------------------------------
/**
 * Executes an SQL query on the default database.
 * Opens the database on the first call to this function during the current HTTP request.
 * Afterwards it reuses the same database connection.
 *
 * @param string $query
 * @param array  $params
 * @return PDOStatement
 */
function database_query ($query, $params = null)
{
  $SQL_KEYWORDS = [
    'SELECT', 'FROM', 'WHERE', 'AND', 'OR', 'JOIN', 'LEFT', 'RIGHT', 'OUTER',
    'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'UNION'
  ];

  global $application;

  $showQuery = function ($dur = null) use ($query, $params, $SQL_KEYWORDS) {
    $query = trim ($query);
    WebConsole::database ('<#section|SQL QUERY>', highlightQuery ($query, $SQL_KEYWORDS, 'identifier'));
    if (!empty($params))
      WebConsole::database ("<#header>Parameters</#header>", $params);
    if (isset($dur))
      WebConsole::database ("<#footer>Query took <b>$dur</b> seconds.</#footer>");
    WebConsole::database ('</#section>');
  };

  if ($application->debugMode)
    $start = microtime (true);
  try {
    $st = db()->prepare ($query);
    $st->execute ($params);
  } catch (PDOException $e) {
    $showQuery ();
    WebConsole::database ('<#footer><#alert>Query failed!</#alert></#footer>');
    WebConsole::throwErrorWithLog ($e);
  }
  if ($application->debugMode) {
    $end = microtime (true);
    $dur = round ($end - $start, 4);
    $showQuery ($dur);
  }
  return $st;
}

function database_begin ()
{
  global $transactionDepth;
  if (++$transactionDepth == 1)
    db()->beginTransaction ();
}

function database_commit ()
{
  global $transactionDepth;
  if (--$transactionDepth == 0)
    db()->commit ();
}

function database_rollback ()
{
  global $transactionDepth;
  if ($transactionDepth > 0) {
    $transactionDepth = 0;
    db()->rollBack ();
  }
}

/**
 * @param string     $query
 * @param array|null $params
 * @return mixed|false False if query result set is empty.
 */
function database_get ($query, array $params = null)
{
  $st = database_query ($query, $params);
  $v = $st->fetchColumn (0);
  $st->closeCursor();
  return $v;
}


