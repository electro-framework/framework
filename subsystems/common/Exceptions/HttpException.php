<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class HttpException extends \Exception
{
  /**
   * Additional information about the error.
   * @var string
   */
  public $info;

  /**
   * @param int    $statusCode
   * @param string $msg  Error description. It should be a single line of unformatted text.
   * @param string $info Additional information about the error. It may contain HTML formatting.
   */
  public function __construct ($statusCode = 500, $msg = '', $info = '')
  {
    parent::__construct ($msg, $statusCode);
    $this->info = $info;
  }

}
