<?php
namespace Electro\Database\Lib;

use PhpKit\ExtPDO\Connection;
use PhpKit\ExtPDO\ExtPDO;

class DebugConnection extends Connection
{
  /**
   * @param array|null $options
   * @return \PhpKit\ExtPDO\ExtPDO|DebugPDO
   */
  function getPdo (array $options = null)
  {
    return $this->pdo ?: ($this->pdo = new DebugPDO (parent::getPdo ($options)));
  }

}
