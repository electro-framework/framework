<?php
namespace Selenia\Exceptions;

use Selenia\Exceptions;

class FileException extends Exceptions\BaseException
{
  const FILE_IS_REQUIRED      = 1;
  const FILE_IS_INVALID       = 2;
  const CAN_NOT_SAVE_FILE     = 3;
  const FIELD_NOT_FOUND       = 4;
  const CAN_NOT_DELETE_FILE   = 5;
  const CAN_NOT_SAVE_TMP_FILE = 6;
  const FILE_TOO_BIG          = 7;
  const FILE_NOT_FOUND        = 8;

  public static $messages     = [
    self::FILE_IS_REQUIRED      => "Tem de seleccionar um ficheiro.",
    self::FILE_IS_INVALID       => "Tipo de ficheiro não suportado.",
    self::CAN_NOT_SAVE_FILE     => "Não é possível guardar o ficheiro no servidor. Verifique as permissões da pasta respectiva: ",
    self::FIELD_NOT_FOUND       => "O campo especificado não existe no formulário.",
    self::CAN_NOT_DELETE_FILE   => "Não é possível remover o ficheiro do servidor. Verifique as permissões da pasta respectiva: ",
    self::CAN_NOT_SAVE_TMP_FILE => "Não é possível criar um ficheiro temporário no servidor. Verifique as permissões da pasta temporária e o espaço em disco disponível.",
    self::FILE_TOO_BIG          => "O ficheiro excede o tamanho máximo permitido de ",
    self::FILE_NOT_FOUND        => "O ficheiro não foi encontrado."
  ];
  public static $statusLookup = [
    self::FILE_IS_REQUIRED      => Exceptions\Status::WARNING,
    self::FILE_IS_INVALID       => Exceptions\Status::WARNING,
    self::CAN_NOT_SAVE_FILE     => Exceptions\Status::ERROR,
    self::FIELD_NOT_FOUND       => Exceptions\Status::FATAL,
    self::CAN_NOT_DELETE_FILE   => Exceptions\Status::ERROR,
    self::CAN_NOT_SAVE_TMP_FILE => Exceptions\Status::ERROR,
    self::FILE_TOO_BIG          => Exceptions\Status::ERROR,
    self::FILE_NOT_FOUND        => Exceptions\Status::ERROR
  ];

  public function __construct ($code, $extra = '')
  {
    parent::__construct (self::$messages[$code] . $extra, self::$statusLookup[$code]);
  }

}
