<?php
namespace Selenia\Database;

class Connection
{
  const ENV_CONFIG_SETTINGS = [
    'DB_CHARSET'     => 'charset',
    'DB_COLLATION'   => 'collation',
    'DB_DATABASE'    => 'database',
    'DB_DRIVER'      => 'driver',
    'DB_HOST'        => 'host',
    'DB_PASSWORD'    => 'password',
    'DB_PORT'        => 'port',
    'DB_PREFIX'      => 'prefix',
    'DB_UNIX_SOCKET' => 'unixSocket',
    'DB_USERNAME'    => 'username',
  ];

  public $charset;
  public $collation;
  public $database;
  public $driver;
  public $host;
  public $password;
  public $port;
  public $prefix;
  public $unixSocket;
  public $username;

  static function getFromEnviroment ()
  {
    $cfg = new static;
    foreach (self::ENV_CONFIG_SETTINGS as $k => $p)
      $cfg->$p = env ($k);
    return $cfg;
  }

  function getProperties ()
  {
    return object_publicProps ($this);
  }

  function isAvailable ()
  {
    return $this->driver && $this->driver !== 'none';
  }

}
