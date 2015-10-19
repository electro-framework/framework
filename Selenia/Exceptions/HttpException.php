<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class HttpException extends \Exception
{
  private static $MESSAGES = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    408 => 'Request Timeout',
    410 => 'Gone',
    415 => 'Unsupported Media Type',
    417 => 'Expectation Failed',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    503 => 'Service Unavailable',
  ];

  public $info = '';

  /**
   * @param int    $statusCode
   * @param string $msg
   * @param string $info
   */
  public function __construct ($statusCode = 500, $msg = '', $info = '')
  {
    if (!$msg) {
      if (isset(self::$MESSAGES[$statusCode]))
        $msg = self::$MESSAGES[$statusCode];
      else $msg = "HTTP status code $statusCode";
    }
    parent::__construct ($msg, $statusCode);
    $this->info = $info;
  }

}
