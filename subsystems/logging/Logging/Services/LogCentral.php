<?php

namespace Electro\Logging\Services;

use Electro\Interfaces\Logging\LogCentralInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LogCentral implements LogCentralInterface
{
  /**
   * @var Logger
   */
  private $logger;
  /**
   * @var LoggerInterface[]
   */
  private $loggers = [];

  public function __construct (Logger $logger)
  {
    $this->logger = $logger;
  }

  function get ($name)
  {
    return get ($this->loggers, $name) ?: new NullLogger;
  }

  function has ($name)
  {
    return isset ($this->loggers[$name]);
  }

  function make ($name)
  {
    return $this->loggers[$name] = new Logger ($name);
  }

  function makeChannel ($name, $enabled = true)
  {
    return $this->loggers[$name] = $this->logger->withName ($name);
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
