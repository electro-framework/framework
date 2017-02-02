<?php

namespace Electro\Logging\Services;

use Electro\Interfaces\Logging\LoggersInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Loggers implements LoggersInterface
{
  private $loggers = [];

  public function __construct () { }

  function make ($name)
  {
    return $this->loggers[$name] = new Logger ($name);
  }


  function get ($name)
  {
    return get ($this->loggers, $name) ?: new NullLogger;
  }

  function has ($name)
  {
    return isset ($this->loggers[$name]);
  }

  function register ($name, LoggerInterface $logger)
  {
    $this->loggers[$name] = $logger;
    return $this;
  }

  function unregister ($name)
  {
    unset ($this->loggers[$name]);
    return $this;
  }

}
