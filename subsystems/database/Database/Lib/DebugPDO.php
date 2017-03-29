<?php
namespace Electro\Database\Lib;

use Electro\Traits\DecoratorTrait;
use PhpKit\ExtPDO\ExtPDO;

/**
 * @property ExtPDO $decorated
 */
class DebugPDO
{
  use DecoratorTrait;

  public function __construct (ExtPDO $pdo)
  {
    $this->decorated = $pdo;
  }

  function __debugInfo ()
  {
    return [
      'decorated' => $this->decorated,
    ];
  }

  public function prepare ($statement, array $driver_options = [])
  {
    return new DebugStatement ($this->decorated->prepare ($statement, $driver_options), $statement, $this);
  }

  public function query ($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = array())
  {
    return new DebugStatement ($this->decorated->query ($statement, $mode, $arg3, $ctorargs), $statement, $this);
  }


}
