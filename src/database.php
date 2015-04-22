<?php

$transactionDepth = 0;
//--------------------------------------------------------------------------
/** Do not call directly! */
function database_open ()
//--------------------------------------------------------------------------
{
  global $db, $application;

  $database = $_ENV['DB_DATABASE'];
  $options  = null;

  if ($_ENV['DB_DRIVER'] == 'sqlite') {

    if ($database != ':memory:') {
      if ($database[0] != '/')
        $database = "$application->baseDirectory/$database";
    }
    $dsn = "sqlite:$database";

  } else {

    $dsn = "{$_ENV['DB_DRIVER']}:host={$_ENV['DB_HOST']};dbname=$database";
    if (isset ($_ENV['DB_PORT']))
      $dsn .= ";port={$_ENV['DB_PORT']}";
    if (isset ($_ENV['DB_UNIX_SOCKET']))
      $dsn .= ";unix_socket={$_ENV['DB_UNIX_SOCKET']}";
    $options = null;

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

  try {
    $db = new PDO ($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
  } catch (PDOException $e) {
    $e =
      new PDOException($e->getMessage () . "\n\nDatabase: " . ErrorHandler::shortFileName ($database), $e->getCode ());
    throw $e;
  }
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
//--------------------------------------------------------------------------
{
  global $db;
  if (defined ('DEBUG_SQL')) {
    echo "<p><pre><li>$query</li><ul>" . print_r ($params, true) . "</ul></pre></p>";
    $start = microtime (true);
  }
  if (!isset($db))
    database_open ();
  $db->setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $st = $db->prepare ($query);
  $st->execute ($params);
  if (defined ('DEBUG_SQL')) {
    $end = microtime (true);
    $dur = round ($end - $start, 4);
    echo "<p>Query took $dur seconds.</p>";
  }
  return $st;
}

function database_begin ()
{
  global $transactionDepth;
  if (++$transactionDepth == 1)
    database_query ('BEGIN');
}

function database_commit ()
{
  global $transactionDepth;
  if (--$transactionDepth == 0) {
//database_rollback();
//exit;
    database_query ('COMMIT');
  }
}

function database_rollback ()
{
  global $transactionDepth;
  if ($transactionDepth > 0) {
    $transactionDepth = 0;
    database_query ('ROLLBACK');
  }
}

function database_get ($query, $params = null)
{
  $st = database_query ($query, $params);
  if ($st)
    return $st->fetchColumn (0);
  return null;
}


