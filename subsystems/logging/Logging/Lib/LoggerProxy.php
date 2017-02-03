<?php

namespace Electro\Logging\Lib;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class LoggerProxy extends AbstractLogger
{
  /** @var bool */
  protected $enabled;
  /** @var LoggerInterface */
  protected $logger;

  public function __construct (LoggerInterface $logger, $enabled = true)
  {
    $this->logger  = $logger;
    $this->enabled = $enabled;
  }

  public function disable ()
  {
    $this->enabled = false;
  }

  public function enable ()
  {
    $this->enabled = true;
  }

  public function isEnabled ()
  {
    return $this->enabled;
  }

  public function log ($level, $message, array $context = [])
  {
    if ($this->enabled)
      $this->logger->log ($level, $message, $context);
  }

}
