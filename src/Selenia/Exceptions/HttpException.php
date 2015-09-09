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

  public function __construct ($statusCode = 500, $msg = '')
  {
    if (!$msg) $msg = "<h1>$statusCode " . get (self::$MESSAGES, $statusCode) . "</h1>";
    parent::__construct ($msg, $statusCode);
  }

}
