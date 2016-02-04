<?php
namespace Selenia\Database;

use PhpKit\Connection;
use PhpKit\ExtPDO;

class DebugConnection extends Connection
{
  /** @var bool */
  private $debugMode;
  /** @var ExtPDO|DebugPDO */
  private $pdo;

  /**
   * @param bool $debugMode
   */
  public function __construct ($debugMode)
  {
    $this->debugMode = $debugMode;
  }


  function getPdo (array $options = null)
  {
    return $this->pdo
      ?: ($this->pdo = $this->debugMode
        ? new DebugPDO(parent::getPdo ($options))
        : parent::getPdo ($options)
      );
  }

}
