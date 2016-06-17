<?php
namespace Electro\Database\Lib;

use PhpKit\Connection;
use PhpKit\ExtPDO;

class DebugConnection extends Connection
{
  /**
   * @param array|null $options
   * @return ExtPDO|DebugPDO
   */
  function getPdo (array $options = null)
  {
    return $this->pdo ?: ($this->pdo = new DebugPDO (parent::getPdo ($options)));
  }

}
