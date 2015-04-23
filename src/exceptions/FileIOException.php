<?php
namespace selene\matisse\exceptions;

class FileIOException extends MatisseException
{
  public function __construct ($filename, $mode = 'read')
  {
    switch ($mode) {
      case 'read':
        $m = "was not found";
        break;
      case 'write':
        $m = "can't be written to";
        break;
      case 'delete':
        $m = "can't be deleted";
        break;
      default:
        throw new \RuntimeException("Invalid mode $mode.");
    }
    parent::__construct ("File '$filename' $m.");
  }

}
